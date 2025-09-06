<?php

namespace App\Jobs;

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

    // Properti untuk menyimpan sistem yang akan diproses
    protected System $system;

    /**
     * Create a new job instance.
     */
    public function __construct(System $system)
    {
        $this->system = $system;
    }

    /**
     * Execute the job.
     * Di sinilah semua pekerjaan berat dilakukan di latar belakang.
     */
    public function handle(AIService $aiService): void
    {
        Log::info("Memulai sinkronisasi untuk sistem: {$this->system->name}");

        $repoPath = str_replace('https://github.com/', '', $this->system->repository_url);
        $apiUrl = "https://api.github.com/repos/{$repoPath}/commits";

        try {
            $response = Http::withToken(config('services.github.token'))
                ->get($apiUrl, [
                    'sha' => $this->system->branch ?? 'main',
                    'since' => now()->startOfDay()->toIso8601String(),
                    'per_page' => 100,
                ]);

            $response->throw(); // Lempar exception jika gagal (4xx atau 5xx)

            $commits = $response->json();
            if (empty($commits)) {
                Log::info("Tidak ada commit baru untuk disinkronkan pada sistem: {$this->system->name}");
                return;
            }

            $newCommits = 0;
            // Balik urutan array agar diproses dari commit terlama ke terbaru
            foreach (array_reverse($commits) as $commit) {
                $commitHash = $commit['sha'];

                // Lewati jika commit sudah pernah diproses
                if (Report::where('commit_hash', $commitHash)->exists()) {
                    continue;
                }

                // Bungkus proses per commit dengan try-catch agar jika satu gagal, yang lain tetap jalan
                try {
                    $commitMessage = $commit['commit']['message'];

                    // Panggilan API kedua untuk mendapatkan detail dan diff
                    $detailResponse = Http::withToken(config('services.github.token'))->get($commit['url']);

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

                    // Panggil AIService untuk menghasilkan deskripsi dan snippets
                    $aiResult = $aiService->generateReportDetails($commitMessage, $changedFiles, $rawDiff);

                    // Buat laporan utama
                    $report = Report::create([
                        'system_id' => $this->system->id,
                        'title' => $commitMessage,
                        'description' => $aiResult['description'],
                        'raw_diff' => $rawDiff,
                        'status' => 'pending',
                        'commit_hash' => $commitHash,
                        'started_at' => now(),
                        // 'work_type' akan menggunakan default 'normal' dari database
                    ]);

                    // Jika AI memberikan snippets, simpan ke tabel code_snippets
                    if (!empty($aiResult['snippets'])) {
                        $report->codeSnippets()->createMany($aiResult['snippets']);
                    }

                    $newCommits++;
                } catch (\Exception $e) {
                    Log::error("Gagal memproses commit {$commitHash} untuk sistem {$this->system->name}: " . $e->getMessage());
                    // Lanjutkan ke commit berikutnya
                    continue;
                }
            }

            Log::info("Sinkronisasi selesai untuk sistem: {$this->system->name}. {$newCommits} laporan baru ditambahkan.");
        } catch (\Exception $e) {
            Log::error("Gagal total saat sinkronisasi sistem {$this->system->name}: " . $e->getMessage());
        }
    }
}
