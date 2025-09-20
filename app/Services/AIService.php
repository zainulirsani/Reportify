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

    /**
     * PERBAIKAN 1: Tambahkan parameter opsional $taskDescription
     */
    public function generateReportDetails(string $commitMessage, string $changedFiles, ?string $gitDiff = null, ?string $taskDescription = null): array
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            throw new \Exception('Gemini API key is not set.');
        }

        // PERBAIKAN 2: Logika keputusan sekarang sepenuhnya ada di dalam service ini
        if (strlen($gitDiff ?? '') > self::DIFF_CHARACTER_LIMIT) {
            // Jika diff terlalu besar, gunakan prompt sederhana yang sekarang lebih cerdas
            $prompt = $this->createSimplePrompt($commitMessage, $taskDescription);
        } else {
            // Jika diff ukurannya wajar, gunakan prompt canggih
            $prompt = $this->createAdvancedPrompt($commitMessage, $changedFiles, $gitDiff);
        }

        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
        
        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($apiUrl, [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ]);

        $response->throw();
        
        $rawContent = $response->json('candidates.0.content.parts.0.text', '{}');
        Log::info('Jawaban Mentah dari Gemini AI (Daily):', ['content' => $rawContent]);

        $parsedJson = $this->parseAiResponse($rawContent);

        if ($parsedJson === null) {
            return [
                'description' => "Analisis AI Gagal (Format Respons Tidak Valid). Pesan Commit: " . $commitMessage,
                'snippets' => [],
            ];
        }

        return [
            'description' => $parsedJson['description'] ?? "AI tidak memberikan deskripsi. Pesan Commit: " . $commitMessage,
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
    private function createSimplePrompt(string $commitMessage, ?string $taskDescription = null): string
    {
        $promptText = "Anda adalah seorang asisten developer senior. Perubahan kode untuk sebuah commit terlalu besar untuk dianalisis.
            Tugas Anda adalah membuat deskripsi laporan pekerjaan yang informatif dengan **menghubungkan dua informasi** berikut:
            1.  **Deskripsi Tugas Asli (Tujuan/Mengapa):** Ini menjelaskan tujuan awal dari pekerjaan yang dilakukan.
            2.  **Pesan Commit (Hasil/Apa):** Ini menjelaskan apa yang developer selesaikan.

            Buatlah satu paragraf laporan dalam Bahasa Indonesia yang mengalir dengan baik, menjelaskan apa yang dikerjakan dalam konteks tujuan tugasnya.

            KEMBALIKAN JAWABAN HANYA DALAM FORMAT JSON YANG VALID SEPERTI CONTOH INI:
            ```json
            {
            \"description\": \"(Tulis paragraf laporan gabungan di sini)\",
            \"snippets\": []
            }
            ";
        if ($taskDescription) {
            $promptText .= "\n**1. Deskripsi Tugas Asli (Tujuan/Mengapa):**\n{$taskDescription}\n";
        }

        $promptText .= "\n**2. Pesan Commit (Hasil/Apa):**\n{$commitMessage}\n---";

        return $promptText;
    }

    public function generateWeeklySummary(string $dailyReportsContext): array
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            throw new \Exception('Gemini API key is not set.');
        }

        $prompt = $this->createWeeklySummaryPrompt($dailyReportsContext);
        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(90)->post($apiUrl, [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ]);

        $response->throw();

        $rawContent = $response->json('candidates.0.content.parts.0.text', '{}');
        Log::info('Jawaban Mentah dari Gemini AI (Weekly):', ['content' => $rawContent]);

        $parsedJson = $this->parseAiResponse($rawContent);

        if ($parsedJson === null) {
            return ['systems' => []];
        }

        return [
            'systems' => $parsedJson['systems'] ?? [],
        ];
    }
    private function parseAiResponse(string $rawContent): ?array
    {
        $jsonString = null;
        if (preg_match('/```json\s*(.*?)\s*```/s', $rawContent, $matches)) {
            $jsonString = $matches[1];
        } elseif (preg_match('/(\{.*\}|\[.*\])/s', $rawContent, $matches)) {
            $jsonString = $matches[0];
        }

        if ($jsonString) {
            $decoded = json_decode($jsonString, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        return null;
    }
    private function createWeeklySummaryPrompt(string $dailyReportsContext): string
    {
        return "
                Anda adalah seorang lead developer yang teliti, bertugas membuat laporan progres mingguan untuk manajemen.
                Berikut adalah data mentah laporan harian dari tim Anda selama seminggu terakhir, berada di dalam tag <DATA_LAPORAN>.

                <DATA_LAPORAN>
                {$dailyReportsContext}
                </DATA_LAPORAN>

                Tugas Anda adalah memproses semua data di dalam tag <DATA_LAPORAN> dan mengelompokkannya berdasarkan nama proyek/sistem.

                Untuk **SETIAP** sistem yang dikerjakan, Anda harus menghasilkan:
                a. Sebuah **daftar singkat (bullet points)** berisi poin-poin pekerjaan yang dilakukan (rangkum dari judul laporannya).
                b. Satu **paragraf ringkasan** yang menjelaskan pekerjaan pada sistem tersebut secara lebih detail (rangkum dari deskripsi laporannya).

                **ATURAN PENTING: ANDA DILARANG KERAS MENGGUNAKAN INFORMASI ATAU MENGARANG NAMA PROYEK APAPUN YANG TIDAK DISEBUTKAN SECARA EKSPLISIT DI DALAM TAG <DATA_LAPORAN>.**

                KEMBALIKAN JAWABAN HANYA DALAM FORMAT JSON YANG VALID SEPERTI CONTOH DI BAWAH INI, TANPA TEKS LAIN.
                Struktur JSON harus berupa object dengan satu key utama \"systems\" yang berisi array dari setiap proyek.

                ```json
                {
                    \"systems\": [
                        {
                        \"name\": \"(Nama Sistem 1 dari data)\",
                        \"tasks\": [
                            \"(Poin pekerjaan 1 pada sistem 1)\",
                            \"(Poin pekerjaan 2 pada sistem 1)\"
                        ],
                        \"summary_paragraph\": \"(Tulis paragraf ringkasan untuk pekerjaan di sistem 1 di sini)\"
                        },
                        {
                        \"name\": \"(Nama Sistem 2 dari data)\",
                        \"tasks\": [
                            \"(Poin pekerjaan 1 pada sistem 2)\"
                        ],
                        \"summary_paragraph\": \"(Tulis paragraf ringkasan untuk pekerjaan di sistem 2 di sini)\"
                        }
                    ]
                }
            ";
    }


    public function rewriteTextForClarity(string $technicalText): array
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            throw new \Exception('Gemini API key is not set.');
        }

        $prompt = $this->createRewritePrompt($technicalText);
        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($apiUrl, [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ]);

        $response->throw();

        // Gunakan helper parseAiResponse yang sudah tangguh
        $rawContent = $response->json('candidates.0.content.parts.0.text', '{}');
        $parsedJson = $this->parseAiResponse($rawContent);

        // Kembalikan array 'suggestions', atau array kosong jika gagal
        return $parsedJson['suggestions'] ?? [];
    }

    private function createRewritePrompt(string $technicalText): string
{
    return "Tulis ulang teks teknis berikut menjadi 3 versi kalimat yang lebih profesional dan mudah dimengerti untuk laporan.
    
        ATURAN:
        - Jawaban WAJIB dalam format JSON.
        - Jelaskan masalah yang terjadi dan solusinya yang bisa diambil.
        - Gunakan Bahasa Indonesia yang baik dan benar.
        - JANGAN mengarang informasi baru.
        - JANGAN menjelaskan apa yang sedang kamu lakukan.

        CONTOH JAWABAN JSON:
        ```json
        {
        \"suggestions\": [
            \"(Versi 1)\",
            \"(Versi 2)\",
            \"(Versi 3)\"
        ]
        }
        {$technicalText}

            ";
    }
}
