<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WeeklyReportController extends Controller
{
    protected ReportService $reportService;
    protected AIService $aiService;

    public function __construct(ReportService $reportService, AIService $aiService)
    {
        $this->reportService = $reportService;
        $this->aiService = $aiService;
    }

    /**
     * Menampilkan halaman awal untuk generate laporan.
     */
    public function index()
    {
        return view('user.pages.reportsWeekly');
    }

    /**
     * Memproses permintaan untuk membuat laporan mingguan.
     */
    public function generate(Request $request)
    {
        try {
            $user = Auth::user();
            
            // 1. Ambil semua laporan relevan dari 7 hari terakhir
            $reports = $this->reportService->getReportsForWeeklySummary($user);

            if ($reports->isEmpty()) {
                return back()->with('info', 'Tidak ada laporan "completed" yang ditemukan dalam 7 hari terakhir untuk dibuatkan ringkasan.');
            }

            // 2. Format data laporan menjadi satu teks untuk konteks AI
            $dailyReportsContext = '';
            foreach ($reports as $report) {
                $dailyReportsContext .= "Proyek: {$report->system->name}\n";
                $dailyReportsContext .= "Judul Laporan: {$report->title}\n";
                $dailyReportsContext .= "Deskripsi: {$report->description}\n---\n";
            }
            // dd($dailyReportsContext);
            // 3. Panggil AI untuk membuat ringkasan
            $weeklySummary = $this->aiService->generateWeeklySummary($dailyReportsContext);
            
            // 4. Kirim hasilnya kembali ke view
            return view('user.pages.reportsWeekly', [
                'summary' => $weeklySummary['summary_paragraph'],
                'systems' => $weeklySummary['systems_worked_on'],
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal membuat laporan mingguan: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencoba membuat laporan mingguan. Silakan coba lagi.');
        }
    }
}