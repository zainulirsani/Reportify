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
    // app/Services/AIService.php

    public function generateReportDetails(string $commitMessage, string $changedFiles, ?string $gitDiff = null): array
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            throw new \Exception('Gemini API key is not set.');
        }

        // =====================================================================
        // BAGIAN YANG DIPERBAIKI
        // =====================================================================
        if (strlen($gitDiff ?? '') > self::DIFF_CHARACTER_LIMIT) {
            // JALUR DARURAT: Jika diff terlalu besar, gunakan prompt sederhana.
            $prompt = $this->createSimplePrompt($commitMessage);
        } else {
            // JALUR NORMAL: Jika diff ukurannya wajar, gunakan prompt canggih.
            $prompt = $this->createAdvancedPrompt($commitMessage, $changedFiles, $gitDiff);
        }
        // Baris yang menimpa $prompt sudah dihapus.
        // =====================================================================

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

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'description' => "Analisis AI Gagal (Format Respons Tidak Valid). Pesan Commit: " . $commitMessage,
                'snippets' => [],
            ];
        }

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
        return "Anda adalah seorang asisten yang bertugas mengubah pesan commit menjadi kalimat laporan.
            Tugas Anda adalah **MERANGKUM** pesan commit berikut menjadi sebuah kalimat laporan pekerjaan yang profesional dalam Bahasa Indonesia.

            **ATURAN PENTING: JANGAN MENAMBAHKAN INFORMASI, PENJELASAN, ALASAN, ATAU TUJUAN APAPUN** yang tidak ada secara eksplisit di dalam pesan commit. Cukup ubah formatnya menjadi kalimat laporan.

            KEMBALIKAN JAWABAN HANYA DALAM FORMAT JSON YANG VALID SEPERTI CONTOH INI:
            ```json
                {
                \"description\": \"(Tulis hasil rangkuman pesan commit di sini)\",
                \"snippets\": []
                }
            ```";
    }

    public function generateWeeklySummary(string $dailyReportsContext): array
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            throw new \Exception('Gemini API key is not set.');
        }

        // Perubahan terjadi di dalam method ini
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
        $jsonContent = Str::of($rawContent)->between('```json', '```')->trim();
        if ($jsonContent->isEmpty()) {
            $jsonContent = $rawContent;
        }

        $parsedJson = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'summary_paragraph' => 'AI Gagal memberikan respons JSON yang valid. Respons mentah: ' . $rawContent,
                'systems_worked_on' => [],
            ];
        }

        return [
            'summary_paragraph' => $parsedJson['summary_paragraph'] ?? 'AI gagal membuat ringkasan paragraf.',
            'systems_worked_on' => $parsedJson['systems_worked_on'] ?? [],
        ];
    }

    private function createWeeklySummaryPrompt(string $dailyReportsContext): string
    {
        return " Berikut adalah data mentah laporan pekerjaan harian selama seminggu terakhir. Data ini berada di dalam tag <DATA_LAPORAN>.
            <DATA_LAPORAN>
            {$dailyReportsContext}
            </DATA_LAPORAN>

            Tugas Anda adalah bertindak sebagai seorang karyawan developer yang sangat teliti. Anda diminta untuk membuat laporan mingguan atas perkejaan anda
            Proses teks yang ada di dalam tag <DATA_LAPORAN> di atas dan lakukan dua hal:
            1. Tulis satu ringkasan dalam format paragraf yang merangkum semua pencapaian dan progres. **Ringkasan WAJIB didasarkan HANYA pada informasi di dalam tag <DATA_LAPORAN>**.
            2. Buat daftar (list) nama-nama sistem atau proyek unik yang disebutkan di dalam data dan sebutkan fokus pekerjaannya.

            **ATURAN PENTING: ANDA DILARANG KERAS MENGGUNAKAN INFORMASI ATAU MENGARANG NAMA PROYEK APAPUN YANG TIDAK DISEBUTKAN SECARA EKSPLISIT DI DALAM TAG <DATA_LAPORAN>.**

                KEMBALIKAN JAWABAN HANYA DALAM FORMAT JSON YANG VALID SEPERTI CONTOH DI BAWAH INI, TANPA TEKS LAIN.
                ```json
                {
                    \"summary_paragraph\": \"(Tulis ringkasan paragraf berdasarkan data di sini)\",
                    \"systems_worked_on\": [
                        \"(Nama Sistem 1 dari data)\",
                        \"(Nama Sistem 2 dari data)\"
                    ]
                }
            ";
    }
}
