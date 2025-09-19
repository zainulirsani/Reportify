<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Models\Report;
use App\Models\System;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessSyncCommits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $commitMappings,
        protected int $userId,
        protected int $systemId
    ) {}

    public function handle(AIService $aiService): void
    {
        $user = User::find($this->userId);
        $system = System::find($this->systemId);
        if (!$user || !$system) {
            Log::error("User atau Sistem tidak ditemukan.", ['user_id' => $this->userId, 'system_id' => $this->systemId]);
            return;
        }

        Log::info("Worker memulai eksekusi untuk {$user->name} pada sistem {$system->name} dengan " . count($this->commitMappings) . " pemetaan commit.");

        $newCommits = 0;
        foreach ($this->commitMappings as $mapping) {
            $commitData = $mapping['commit'];
            $taskId = $mapping['task_id'];
            $commitHash = $commitData['sha'];

            try {
                // 1. Kumpulkan semua data mentah
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
                
                // Ambil deskripsi tugas jika ada
                $task = $taskId ? Task::find($taskId) : null;
                $taskDescription = $task ? $task->description : null;

                // 2. Serahkan semua data ke AIService. Biarkan service yang memutuskan.
                $aiResult = $aiService->generateReportDetails(
                    $fullCommitMessage, 
                    $changedFiles, 
                    $rawDiff, 
                    $taskDescription // Kirim deskripsi tugas (bisa null)
                );

                // 3. Simpan hasilnya
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

                // 4. Update status tugas jika terhubung
                if ($task && $task->user_id === $user->id) {
                    $task->update(['status' => 'done']);
                    Log::info("Status tugas {$task->task_code} diperbarui menjadi 'done'.");
                }
                
                $newCommits++;
            } catch (\Exception $e) {
                Log::error("Gagal memproses commit {$commitHash} untuk sistem {$system->name}: " . $e->getMessage());
                continue;
            }
        }
        
        Log::info("Pemrosesan sinkronisasi selesai untuk sistem {$system->name}. {$newCommits} laporan baru ditambahkan.");
    }
}