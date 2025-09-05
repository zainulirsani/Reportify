<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\System;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Disarankan untuk menambahkan verifikasi secret token di sini
        // $secretFromGithub = $request->header('X-Secret-Token');
        // $secretFromEnv = config('services.github.webhook_secret');
        // if ($secretFromGithub !== $secretFromEnv) {
        //     return response()->json(['message' => 'Invalid secret token.'], 403);
        // }

        try {
            // --- 1. Ambil & Validasi Data Awal dari GitHub ---
            $repoUrl = $request->input('repository');
            $commitMessage = $request->input('commit_message');

            if (!$repoUrl || !$commitMessage) {
                Log::warning('Webhook received with missing repository URL or commit message.');
                return response()->json(['message' => 'Missing required payload data.'], 422);
            }
            
            $changedFiles = $request->input('changed_files', '');
            $rawPayload = $request->all();

            // --- 2. Cari Sistem di Database ---
            $formattedRepoUrl = str_replace(['https://', 'www.'], '', $repoUrl);
            $system = System::where('repository_url', 'LIKE', '%' . $formattedRepoUrl . '%')->first();

            if (!$system) {
                Log::warning('Webhook received for an unknown repository: ' . $repoUrl);
                return response()->json(['message' => 'Repository not found.'], 404);
            }

            // --- 3. Siapkan & Panggil Gemini AI ---
            $apiKey = config('services.gemini.api_key');
            if (!$apiKey) {
                throw new Exception('Gemini API key is not set.');
            }

            $prompt = $this->createPrompt($commitMessage, $changedFiles);
            
            // =====================================================================
            // PERBAIKAN UTAMA DI SINI
            // Mengganti ->withToken() menjadi ->withHeaders() sesuai permintaan Gemini API
            // =====================================================================
            
            $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

            $response = Http::withHeaders([
                'X-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($apiUrl, [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ])->throw(); // throw() akan otomatis menangkap error 4xx dan 5xx

            // =====================================================================
            
            $generatedDescription = $response->json('candidates.0.content.parts.0.text', 'Gagal membuat deskripsi otomatis.');
            
            // --- 4. Simpan sebagai Draft Laporan ---
            Report::create([
                'system_id' => $system->id,
                'title' => substr($commitMessage, 0, 255),
                'description' => $generatedDescription,
                'status' => 'pending',
                'started_at' => now(),
                'raw_github_payload' => $rawPayload,
            ]);

            Log::info('New report drafted for system: ' . $system->name);
            return response()->json(['message' => 'Webhook received and report drafted.'], 201);

        } catch (ConnectionException $e) {
            Log::error('AI Service Connection Error: ' . $e->getMessage());
            return response()->json(['message' => 'Could not connect to the AI service.'], 504);

        } catch (QueryException $e) {
            Log::error('Database Error on Webhook Handle: ' . $e->getMessage());
            return response()->json(['message' => 'A database error occurred.'], 500);

        } catch (Exception $e) {
            Log::error('An unexpected error occurred in GitHubWebhookController: ' . $e->getMessage());
            return response()->json([
                'message' => 'An internal server error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function untuk membuat prompt Gemini
     */
    private function createPrompt(string $commitMessage, string $changedFiles): string
    {
        return "Anda adalah asisten developer. Berdasarkan informasi commit berikut, buatlah deskripsi laporan pekerjaan dalam Bahasa Indonesia dengan format paragraf singkat yang profesional. Fokus pada apa yang dikerjakan dan potensi dampaknya.

        Informasi Commit:
        - Pesan Commit: \"$commitMessage\"
        - Daftar File yang diubah:
        $changedFiles

        Buat laporannya:";
    }
}