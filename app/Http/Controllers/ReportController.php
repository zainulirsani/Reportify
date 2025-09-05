<?php

namespace App\Http\Controllers;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\ReportService; // <-- Import ReportService
use App\Services\SystemService; // <-- Import SystemService

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected SystemService $systemService;

    /**
     * Suntikkan kedua service melalui constructor.
     */
    public function __construct(ReportService $reportService, SystemService $systemService)
    {
        $this->reportService = $reportService;
        $this->systemService = $systemService;
    }

    /**
     * Menampilkan halaman daftar laporan.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Delegasikan semua pekerjaan ke service!
        $reports = $this->reportService->getFilteredReportsForUser($user, $request);
        $systems = $this->systemService->getAllSystemsForUser($user);
        // Controller hanya bertugas mengirim data ke view
        return view('user.pages.report', compact('reports', 'systems'));
    }

    public function show(Report $report)
    {
        // Pastikan user hanya bisa melihat laporannya sendiri
        abort_if($report->system->user_id !== Auth::id(), 403);

        return response()->json($report);
    }

    public function update(Request $request, Report $report)
    {
        // Pastikan user hanya bisa mengupdate laporannya sendiri
        abort_if($report->system->user_id !== Auth::id(), 403);

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        try {
            $this->reportService->updateReport($report, $validatedData);
            return redirect()->route('reports')->with('success', 'Laporan berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error("Gagal mengupdate laporan (ID: {$report->id}): " . $e->getMessage());
            
            return back()->with('error', 'Terjadi kesalahan saat mencoba memperbarui laporan.');
        }
    }
}