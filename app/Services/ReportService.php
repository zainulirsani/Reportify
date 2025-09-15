<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\User;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    /**
     * Mengambil data laporan milik user dengan filter dan paginasi.
     *
     * @param User $user
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getFilteredReportsForUser(User $user, Request $request): LengthAwarePaginator
    {
        $query = $user->reports()->with('system');

        $query->when($request->status, fn($q, $status) => $q->where('status', $status));
        $query->when($request->system_id, fn($q, $system_id) => $q->where('system_id', $system_id));
        $query->when($request->work_type, fn($q, $work_type) => $q->where('work_type', $work_type)); // <-- TAMBAHKAN INI

        // TAMBAHKAN LOGIKA FILTER TANGGAL YANG SAMA DI SINI
        $query->when($request->date_filter, function ($q, $filter) use ($request) {
            if ($filter === 'week') {
                return $q->whereBetween('reports.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
            if ($filter === 'month') {
                return $q->whereBetween('reports.created_at', [now()->startOfMonth(), now()->endOfMonth()]);
            }
            if ($filter === 'year') {
                return $q->whereBetween('reports.created_at', [now()->startOfYear(), now()->endOfYear()]);
            }
            if ($filter === 'custom' && $request->start_date && $request->end_date) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                return $q->whereBetween('reports.created_at', [$startDate, $endDate]);
            }
        });

        return $query->latest('reports.created_at')->paginate(10);
    }
    public function updateReport(Report $report, array $validatedData): bool
    {
        // Jika status diubah menjadi 'completed', catat waktu selesainya.
        if (isset($validatedData['status']) && $validatedData['status'] === 'completed') {
            $validatedData['completed_at'] = now();
        }

        return $report->update($validatedData);
    }

     public function getReportsForWeeklySummary(User $user, ?string $category = null): Collection
    {
        // 1. Inisialisasi query builder, BUKAN return.
        // Titik koma (;) di akhir baris with() juga dihapus.
        $query = $user->reports()
            ->whereBetween('reports.created_at', [
                now()->subDays(6)->startOfDay(), // 7 hari ke belakang termasuk hari ini
                now()->endOfDay()
            ])
            ->where('status', 'completed')
            ->with('system:id,name');

        // 2. Sekarang $query sudah ada, kita bisa menambahkan kondisi 'when'
        $query->when($category, function ($q, $cat) {
            return $q->whereHas('system', function ($subQuery) use ($cat) {
                $subQuery->where('category', $cat);
            });
        });

        // 3. Return hasil query SETELAH semua kondisi ditambahkan, dengan ->get()
        return $query->get();
    }
}
