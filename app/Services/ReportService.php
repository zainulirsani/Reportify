<?php

namespace App\Services;

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
        // Mulai query dengan mengambil semua laporan milik user yang sedang login
        // 'with('system')' (Eager Loading) membuat query lebih efisien
        $query = $user->reports()->with('system');

        // Terapkan filter berdasarkan status jika ada di request
        $query->when($request->status, function ($q, $status) {
            return $q->where('status', $status);
        });

        // Terapkan filter berdasarkan sistem/proyek jika ada di request
        $query->when($request->system_id, function ($q, $system_id) {
            return $q->where('system_id', $system_id);
        });

        // Ambil hasil akhir dengan paginasi, urutkan berdasarkan yang terbaru
        return $query->latest('completed_at')->paginate(15);
    }
    public function updateReport(Report $report, array $validatedData): bool
    {
        // Jika status diubah menjadi 'completed', catat waktu selesainya.
        if (isset($validatedData['status']) && $validatedData['status'] === 'completed') {
            $validatedData['completed_at'] = now();
        }

        return $report->update($validatedData);
    }
}