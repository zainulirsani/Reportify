<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\Report;
use App\Models\System;
use App\Services\AIService;
use App\Jobs\ProcessSyncCommits;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SystemService
{
     // 2. Tambahkan properti untuk menampung AIService
    protected AIService $aiService;

    /**
     * 3. Suntikkan AIService melalui constructor.
     * Laravel akan otomatis mengisinya untuk kita.
     */
    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }
    /**
     * Mengambil semua data sistem milik user dengan paginasi.
     *
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function getSystemsForUser(User $user): LengthAwarePaginator
    {
        return $user->systems()->latest()->paginate(10);
    }

    /**
     * Membuat data sistem baru.
     *
     * @param array $validatedData Data yang sudah divalidasi dari request.
     * @param User $user User yang sedang login.
     * @return System
     */
    public function createNewSystem(array $validatedData, User $user): System
    {
        return $user->systems()->create($validatedData);
    }

    /**
     * Mengupdate data sistem yang sudah ada.
     *
     * @param System $system Model sistem yang akan diupdate.
     * @param array $validatedData Data yang sudah divalidasi dari request.
     * @return bool
     */
    public function updateSystem(System $system, array $validatedData): bool
    {
        return $system->update($validatedData);
    }

    /**
     * Menghapus data sistem.
     *
     * @param System $system Model sistem yang akan dihapus.
     * @return bool|null
     */
    public function deleteSystem(System $system): ?bool
    {
        return $system->delete();
    }

    public function getAllSystemsForUser(User $user): Collection
    {
        return $user->systems()->orderBy('name')->get();
    }
    public function getNewCommitsFromGitHub(System $system, User $user): array
    {
        $repoPath = str_replace('https://github.com/', '', $system->repository_url);
        $apiUrl = "https://api.github.com/repos/{$repoPath}/commits";

        $response = Http::withToken(config('services.github.token'))
            ->get($apiUrl, [
                'sha' => $system->branch ?? 'main',
                'since' => now()->startOfDay()->toIso8601String(),
                'per_page' => 100,
                'author' => $user->email,
            ]);
        
        $response->throw();
        $allCommits = $response->json();
        if (empty($allCommits)) {
            return [];
        }

        // Ambil semua hash commit yang sudah ada di DB untuk user ini
        $existingHashes = Report::whereIn('commit_hash', collect($allCommits)->pluck('sha'))
            ->pluck('commit_hash')
            ->toArray();

        // Filter dan kembalikan hanya commit yang hash-nya BELUM ADA di database
        return collect($allCommits)
            ->whereNotIn('sha', $existingHashes)
            ->values() // Reset keys to ensure it's a clean array for JSON
            ->all();
    }

    public function processMappedCommits(array $commitMappings, User $user, System $system): int
    {
        Log::info("Memulai pemrosesan sinkron untuk {$user->name} pada sistem {$system->name}.");

        $newCommits = 0;
        foreach ($commitMappings as $mapping) {
            $commitData = $mapping['commit'];
            $taskId = $mapping['task_id'];
            $commitHash = $commitData['sha'];
            
            try {
                $fullCommitMessage = $commitData['commit']['message'];
                $parts = explode("\n\n", $fullCommitMessage, 2);
                $commitSubject = trim($parts[0]);
                
                $detailResponse = Http::withToken(config('services.github.token'))->get($commitData['url']);
                $rawDiff = '';
                $changedFiles = 'N/A';

                if ($detailResponse->successful()) {
                    $commitDetails = $detailResponse->json();
                    if (!empty($commitDetails['files'])) {
                        $diffParts = [];
                        foreach ($commitDetails['files'] as $file) {
                            if (!empty($file['patch'])) {
                                $diffParts[] = "--- a/{$file['filename']}\n+++ b/{$file['filename']}\n" . $file['patch'];
                            }
                        }
                        $rawDiff = implode("\n\n", $diffParts);
                        $changedFiles = collect($commitDetails['files'])->pluck('filename')->implode(', ');
                    }
                } else {
                    Log::warning("Gagal mengambil detail diff untuk commit: {$commitHash}");
                }
                
                $aiResult = $this->aiService->generateReportDetails($fullCommitMessage, $changedFiles, $rawDiff);

                $report = Report::create([
                    'system_id' => $system->id,
                    'task_id' => $taskId,
                    'title' => $commitSubject,
                    'description' => $aiResult['description'],
                    'raw_diff' => $rawDiff,
                    'status' => 'pending',
                    'commit_hash' => $commitHash,
                    'started_at' => now(),
                ]);

                if (!empty($aiResult['snippets'])) {
                    $report->codeSnippets()->createMany($aiResult['snippets']);
                }

                if ($taskId) {
                    $task = Task::find($taskId);
                    if ($task && $task->user_id === $user->id) {
                        $task->update(['status' => 'done']);
                        Log::info("Status tugas {$task->task_code} diperbarui menjadi 'done'.");
                    }
                }
                $newCommits++;
            } catch (\Exception $e) {
                Log::error("Gagal memproses commit {$commitHash} untuk sistem {$system->name}: " . $e->getMessage());
                continue;
            }
        }
        
        Log::info("Pemrosesan sinkron selesai. {$newCommits} laporan baru ditambahkan.");
        return $newCommits;
    }
}
