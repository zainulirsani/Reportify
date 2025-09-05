<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Mengambil dan mengolah semua data yang dibutuhkan untuk halaman dashboard.
     *
     * @param User $user
     * @return array
     */
    public function getDashboardData(User $user): array
    {
        // 1. Data untuk Kartu Statistik
        $totalSystems = $user->systems()->count();

        // DIPERBAIKI: Menambahkan 'reports.created_at' untuk menghilangkan ambiguitas
        $commitsToday = $user->reports()->whereDate('reports.created_at', today())->count();

        // 2. Data untuk Tabel Riwayat Laporan Terbaru
        // DIPERBAIKI: Menambahkan 'reports.created_at' pada latest()
        $recentReports = $user->reports()->with('system')->latest('reports.created_at')->take(5)->get();
        
        // 3. Data untuk daftar sistem/proyek
        $systems = $user->systems;

        // 4. Data untuk Chart (juga perlu diperbaiki di helper method)
        list($chartLabels, $chartDatasets) = $this->prepareChartData($user);

        // 5. Kembalikan semua data dalam satu array
        return [
            'totalSystems' => $totalSystems,
            'commitsToday' => $commitsToday,
            'recentReports' => $recentReports,
            'systems' => $systems,
            'chartLabels' => $chartLabels,
            'chartDatasets' => $chartDatasets,
        ];
    }

    /**
     * Helper method untuk menyiapkan data chart.
     * Logikanya kita pindahkan dari file Blade ke sini.
     *
     * @param User $user
     * @return array
     */
    private function prepareChartData(User $user): array
    {
        $startDate = now()->subDays(4)->startOfDay();
        
        // DIPERBAIKI: Menambahkan 'reports.created_at' di dalam 'where'
        $reportsForChart = $user->reports()
            ->where('reports.created_at', '>=', $startDate)
            ->select('system_id', 'reports.created_at') // Juga perbaiki di select untuk kejelasan
            ->with('system:id,name')
            ->get();


        if ($reportsForChart->isEmpty()) {
            return [ collect(), [] ];
        }

        // Buat label untuk 5 hari terakhir
        $chartLabels = collect();
        for ($i = 4; $i >= 0; $i--) {
            $chartLabels->push(now()->subDays($i)->format('d M'));
        }

        $systemsForChart = $reportsForChart->pluck('system.name')->unique()->values();
        $datasets = [];
        $colors = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444'];

        foreach ($systemsForChart as $index => $systemName) {
            $data = [];
            foreach ($chartLabels as $label) {
                $date = Carbon::createFromFormat('d M', $label);
                $count = $reportsForChart
                    ->where('system.name', $systemName)
                    ->where('created_at', '>=', $date->startOfDay())
                    ->where('created_at', '<=', $date->endOfDay())
                    ->count();
                $data[] = $count;
            }
            $datasets[] = [
                'label' => $systemName,
                'data' => $data,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)] . '33',
                'tension' => 0.2,
                'fill' => true,
            ];
        }

        return [$chartLabels, $datasets];
    }
}