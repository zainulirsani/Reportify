<?php

namespace App\Exports;
use Carbon\Carbon;
use App\Models\Report;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportsExport implements FromQuery, WithHeadings, WithMapping
{
    // Tambahkan properti untuk filter tanggal
    protected $system_id, $status, $date_filter, $start_date, $end_date;

    /**
     * Menerima parameter filter dari Controller, termasuk filter tanggal.
     */
    public function __construct($system_id, $status, $date_filter, $start_date, $end_date)
    {
        $this->system_id = $system_id;
        $this->status = $status;
        $this->date_filter = $date_filter;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    /**
     * Mendefinisikan query untuk mengambil data dari database.
     */
    public function query()
    {
        $query = Report::query()->with('system')
            ->whereHas('system', function ($q) {
                $q->where('user_id', auth()->id());
            });

        // Filter status dan sistem yang sudah ada
        $query->when($this->status, fn ($q) => $q->where('status', $this->status));
        $query->when($this->system_id, fn ($q) => $q->where('system_id', $this->system_id));

        // TAMBAHKAN LOGIKA FILTER TANGGAL DI SINI
        $query->when($this->date_filter, function ($q) {
            if ($this->date_filter === 'week') {
                return $q->whereBetween('reports.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            }
            if ($this->date_filter === 'month') {
                return $q->whereBetween('reports.created_at', [now()->startOfMonth(), now()->endOfMonth()]);
            }
            if ($this->date_filter === 'year') {
                return $q->whereBetween('reports.created_at', [now()->startOfYear(), now()->endOfYear()]);
            }
            if ($this->date_filter === 'custom' && $this->start_date && $this->end_date) {
                $startDate = Carbon::parse($this->start_date)->startOfDay();
                $endDate = Carbon::parse($this->end_date)->endOfDay();
                return $q->whereBetween('reports.created_at', [$startDate, $endDate]);
            }
        });

        return $query->latest('reports.created_at');
    }
    /**
     * Mendefinisikan header untuk kolom-kolom di Excel.
     */
    public function headings(): array
    {
        return [
            'ID Laporan',
            'Nama Proyek',
            'Judul Task',
            'Status',
            'Deskripsi',
            'Tanggal Mulai',
            'Tanggal Selesai',
        ];
    }

    /**
     * Memetakan data dari setiap model Report ke baris Excel.
     * Di sini kita bisa memformat data sesuai keinginan.
     * @param \App\Models\Report $report
     */
    public function map($report): array
    {
        return [
            $report->id,
            $report->system->name,
            $report->title,
            ucfirst(str_replace('_', ' ', $report->status)),
            $report->description,
            $report->started_at->format('d-m-Y H:i:s'),
            $report->completed_at ? $report->completed_at->format('d-m-Y H:i:s') : 'N/A',
        ];
    }
}