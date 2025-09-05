<?php

namespace App\Exports;

use App\Models\Report;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReportsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $system_id;
    protected $status;

    /**
     * Menerima parameter filter dari Controller.
     */
    public function __construct($system_id, $status)
    {
        $this->system_id = $system_id;
        $this->status = $status;
    }

    /**
     * Mendefinisikan query untuk mengambil data dari database.
     * Menggunakan FromQuery sangat efisien untuk data besar.
     */
    public function query()
    {
        // Logika query ini sama persis dengan yang ada di ReportService kita
        $query = Report::query()->with('system')
            ->whereHas('system', function ($q) {
                $q->where('user_id', auth()->id());
            });

        $query->when($this->status, function ($q) {
            return $q->where('status', $this->status);
        });

        $query->when($this->system_id, function ($q) {
            return $q->where('system_id', $this->system_id);
        });

        return $query->latest('completed_at');
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