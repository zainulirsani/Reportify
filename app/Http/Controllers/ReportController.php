<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use App\Services\TaskService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Exports\ReportsExport; // <-- Import Export Class
use App\Services\ReportService; // <-- Import ReportService
use App\Services\SystemService; // <-- Import SystemService
use Maatwebsite\Excel\Facades\Excel; // <-- Import Facade Excel

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected SystemService $systemService;
    protected TaskService $taskService;

    /**
     * Suntikkan kedua service melalui constructor.
     */
    public function __construct(ReportService $reportService, SystemService $systemService, TaskService $taskService)
    {
        $this->reportService = $reportService;
        $this->systemService = $systemService;
        $this->taskService = $taskService;
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
        $openTasks = $this->taskService->getOpenTasksForUser(Auth::user());
        // Controller hanya bertugas mengirim data ke view
        return view('user.pages.report', compact('reports', 'systems','openTasks'));
    }

    public function show(Report $report)
    {
        // Pastikan user hanya bisa melihat laporannya sendiri
        abort_if($report->system->user_id !== Auth::id(), 403);

        return response()->json($report->load('codeSnippets'));
    }

    public function update(Request $request, Report $report)
    {
        // Pastikan user hanya bisa mengupdate laporannya sendiri
        abort_if($report->system->user_id !== Auth::id(), 403);

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:pending,in_progress,completed',
            'work_type' => 'required|in:normal,overtime', // <-- Tambahkan ini
        ]);

        try {
            $this->reportService->updateReport($report, $validatedData);
            return redirect()->route('reports')->with('success', 'Laporan berhasil diperbarui!');
        } catch (\Exception $e) {
            Log::error("Gagal mengupdate laporan (ID: {$report->id}): " . $e->getMessage());

            return back()->with('error', 'Terjadi kesalahan saat mencoba memperbarui laporan.');
        }
    }

    public function export(Request $request)
    {
        $system_id = $request->query('system_id');
        $status = $request->query('status');
        $work_type = $request->query('work_type'); // <-- Pastikan baris ini ada
        $date_filter = $request->query('date_filter');
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        $fileName = 'reports_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Pastikan $work_type diteruskan di sini
        return Excel::download(new ReportsExport($system_id, $status, $work_type, $date_filter, $start_date, $end_date), $fileName);
    }

    public function storeManual(Request $request)
    {
        $validatedData = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'description' => 'required|string',
            'attachment_before' => 'nullable|image|max:2048', // Maks 2MB
            'attachment_after' => 'nullable|image|max:2048',
        ]);

        try {
            $this->reportService->createManualReport($validatedData, Auth::user());
            return redirect()->route('reports.index')->with('success', 'Laporan manual berhasil ditambahkan!');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan laporan manual: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat menyimpan laporan.');
        }
    }
}
