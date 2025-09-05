<?php

namespace App\Http\Controllers;

use App\Services\DashboardService; // <-- Import service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Menampilkan halaman dashboard dengan data nyata.
     */
    public function index()
    {
        // Panggil service untuk mendapatkan semua data yang kita butuhkan
        $dashboardData = $this->dashboardService->getDashboardData(Auth::user());
        $hour = now()->hour; // Menggunakan helper Carbon/Laravel, lebih baik dari date('H')
        
        $greeting = match (true) {
            $hour < 11 => 'Selamat Pagi',
            $hour < 15 => 'Selamat Siang',
            $hour < 19 => 'Selamat Sore',
            default => 'Selamat Malam',
        };
        
        // Tambahkan variabel $greeting ke dalam array data yang akan dikirim ke view
        $dashboardData['greeting'] = $greeting;
        // Kirim array data ke view.
        // Laravel akan otomatis mengekstrak key dari array menjadi variabel.
        return view('user.pages.dashboard', $dashboardData);
    }
}