<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class AIService
{
    /**
     * Menghasilkan deskripsi DAN potongan kode dari data commit.
     * Mengembalikan array terstruktur.
     */

    private const DIFF_CHARACTER_LIMIT = 15000;
    public function generateReportDetails(string $commitMessage, string $changedFiles, ?string $gitDiff = null): array
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            throw new \Exception('Gemini API key is not set.');
        }
        if (strlen($gitDiff ?? '') > self::DIFF_CHARACTER_LIMIT) {
            // JALUR DARURAT: Jika diff terlalu besar, jangan kirim diff-nya.
            // Buat prompt sederhana yang hanya menggunakan pesan commit.
            $prompt = $this->createSimplePrompt($commitMessage);
        } else {
            // JALUR NORMAL: Jika diff ukurannya wajar, gunakan prompt canggih.
            $prompt = $this->createAdvancedPrompt($commitMessage, $changedFiles, $gitDiff);
        }
        $prompt = $this->createAdvancedPrompt($commitMessage, $changedFiles, $gitDiff);
        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($apiUrl, [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ]);

        $response->throw();

        $rawContent = $response->json('candidates.0.content.parts.0.text', '{}');
        Log::info('Jawaban Mentah dari Gemini AI:', ['content' => $rawContent]);
        $jsonContent = Str::of($rawContent)->between('```json', '```')->trim();
        if ($jsonContent->isEmpty()) {
            $jsonContent = $rawContent;
        }

        $parsedJson = json_decode($jsonContent, true);

        return [
            'description' => $parsedJson['description'] ?? 'Gagal membuat deskripsi otomatis.',
            'snippets' => $parsedJson['snippets'] ?? [],
        ];
    }

    /**
     * Helper method privat untuk membuat template prompt yang akan dikirim ke AI.
     */
    private function createAdvancedPrompt(string $commitMessage, string $changedFiles, ?string $gitDiff = null): string
    {
        $promptText = "Anda adalah seorang asisten developer senior yang bertugas mereview kode. Berdasarkan informasi commit berikut, lakukan dua hal:
        1. Buat deskripsi laporan pekerjaan dalam Bahasa Indonesia dengan format paragraf singkat yang profesional.
        2. Pilih 1 sampai 3 potongan kode (snippets) paling penting dari perubahan kode (diff) yang diberikan. Untuk setiap snippet, berikan deskripsi singkat tentang apa yang dilakukannya.

        KEMBALIKAN JAWABAN HANYA DALAM FORMAT JSON YANG VALID SEPERTI CONTOH INI, TANPA TEKS PEMBUKA ATAU PENUTUP LAINNYA:
        ```json
        {
            \"description\": \"(Tulis deskripsi laporan paragraf di sini)\",
            \"snippets\": [
                {
                \"language\": \"(misal: php, js, css)\",
                \"description\": \"(Tulis deskripsi singkat untuk snippet ini)\",
                \"content\": \"(Tempelkan potongan kode diff di sini, pertahankan baris + dan -)\"
                }
            ]
        }";
        if ($gitDiff && !empty($gitDiff)) {
            $promptText .= "\n\nBerikut adalah perubahan kode (diff) yang dilakukan:\n```diff\n$gitDiff\n```";
        } else {
            $promptText .= "\n\nTidak ada perubahan kode yang diberikan, kembalikan array snippets sebagai array kosong.";
        }
        return $promptText;
    }
    private function createSimplePrompt(string $commitMessage): string
    {
        return "Anda adalah seorang asisten developer senior. Perubahan kode untuk commit ini terlalu besar untuk dianalisis. 
            Tolong buatkan deskripsi laporan pekerjaan singkat dalam format paragraf Bahasa Indonesia HANYA BERDASARKAN PESAN COMMIT berikut.

            KEMBALIKAN JAWABAN HANYA DALAM FORMAT JSON YANG VALID SEPERTI CONTOH INI:
            ```json
            {
                \"description\": \"(Tulis deskripsi laporan paragraf di sini berdasarkan pesan commit)\",
                \"snippets\": []
            }
            ```";
    }
}

