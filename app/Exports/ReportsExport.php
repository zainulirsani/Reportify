<?php

namespace App\Exports;

use App\Models\Report;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $system_id, $status, $work_type, $date_filter, $start_date, $end_date;

    public function __construct($system_id, $status, $work_type, $date_filter, $start_date, $end_date)
    {
        $this->system_id = $system_id;
        $this->status = $status;
        $this->work_type = $work_type;
        $this->date_filter = $date_filter;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function query()
    {
        $query = Report::query()->with(['system', 'codeSnippets'])
            ->whereHas('system', function ($q) {
                $q->where('user_id', auth()->id());
            });

        $query->when($this->status, fn ($q) => $q->where('status', $this->status));
        $query->when($this->system_id, fn ($q) => $q->where('system_id', $this->system_id));
        $query->when($this->work_type, fn ($q) => $q->where('work_type', $this->work_type));

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
     * Mendefinisikan header. Urutan di sini HARUS SAMA dengan urutan di map().
     */
    public function headings(): array
    {
        return [
            'ID Laporan',       // 1
            'Nama Proyek',      // 2
            'Judul Task',       // 3
            'Status',           // 4
            'Jenis Pekerjaan',  // 5
            'Deskripsi',        // 6
            'Potongan Kode',    // 7
            'Tanggal Mulai',    // 8
            'Tanggal Selesai',  // 9
        ];
    }

    /**
     * Memetakan data. Urutan di sini HARUS SAMA dengan urutan di headings().
     * @param \App\Models\Report $report
     */
    public function map($report): array
    {
        $snippetText = '';
        if ($report->codeSnippets->isNotEmpty()) {
            foreach ($report->codeSnippets as $snippet) {
                $snippetText .= "Deskripsi Snippet: " . ($snippet->description ?? 'N/A') . "\n";
                $snippetText .= "--------------------------------------------------\n";
                $snippetText .= $snippet->content . "\n\n";
            }
        } else {
            $snippetText = 'Tidak ada snippet yang diekstrak oleh AI untuk commit ini.';
        }

        return [
            $report->id,                                                    // 1
            $report->system->name,                                          // 2
            $report->title,                                                 // 3
            ucfirst(str_replace('_', ' ', $report->status)),                // 4
            $report->work_type === 'overtime' ? 'Lembur' : 'Normal',        // 5
            $report->description,                                           // 6
            trim($snippetText),                                             // 7
            $report->started_at->format('d-m-Y H:i:s'),                     // 8
            $report->completed_at ? $report->completed_at->format('d-m-Y H:i:s') : 'N/A', // 9
        ];
    }

    /**
     * Mengatur lebar kolom secara manual.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 10, // ID Laporan
            'B' => 30, // Nama Proyek
            'C' => 45, // Judul Task
            'D' => 15, // Status
            'E' => 15, // Jenis Pekerjaan
            'F' => 50, // Deskripsi
            'G' => 60, // Potongan Kode
            'H' => 20, // Tanggal Mulai
            'I' => 20, // Tanggal Selesai
        ];
    }

    /**
     * Menerapkan styling (Header Bold dan Wrap Text).
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style baris pertama (header)
            1    => ['font' => ['bold' => true]],

            // Terapkan Wrap Text pada kolom F (Deskripsi) dan G (Potongan Kode)
            'F'  => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
            'G'  => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
        ];
    }
}