<?php

namespace App\Http\Controllers;

use App\Models\System;
use App\Services\SystemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // <-- Jangan lupa import Log
use Exception; // <-- Import Exception class

class SystemController extends Controller
{
    protected SystemService $systemService;

    public function __construct(SystemService $systemService)
    {
        $this->systemService = $systemService;
    }

    public function index()
    {
        // Untuk method read (GET), biasanya error akan ditangani oleh exception handler bawaan Laravel.
        // Namun jika diperlukan, bisa juga ditambahkan try-catch di sini.
        $systems = $this->systemService->getSystemsForUser(auth()->user());
        return view('user.pages.system', compact('systems'));
    }

    public function create()
    {
        return view('user.pages.systems.create');
    }

    public function sync(System $system)
    {
        abort_if($system->user_id !== auth()->id(), 403);

        try {
            $newCommitsCount = $this->systemService->syncCommitsFromGitHub($system, auth()->user());
            // Ganti pesan suksesnya
            return redirect()->route('systems')->with('success', "Sinkronisasi selesai! {$newCommitsCount} laporan baru untuk Anda berhasil ditambahkan.");
        } catch (\Exception $e) {
            Log::error('Gagal memulai sinkronisasi: ' . $e->getMessage());
            return redirect()->route('systems')->with('error', 'Gagal memulai proses sinkronisasi.');
        }
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'repository_url' => 'required|string|max:255|url',
            'description' => 'nullable|string',
            'category' => 'required|string|in:internal,eksternal',
        ]);
        try {
            // Jalankan logika utama di dalam blok 'try'
            $this->systemService->createNewSystem($validatedData, auth()->user());
            return redirect()->route('systems')->with('success', 'Sistem baru berhasil ditambahkan!');
        } catch (Exception $e) {
            // Jika terjadi error, tangkap di sini
            Log::error('Gagal menyimpan sistem baru: ' . $e->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan saat mencoba menyimpan sistem. Silakan coba lagi.')
                ->withInput(); // withInput() untuk mengembalikan data inputan user ke form
        }
    }

    public function edit(System $system)
    {
        abort_if($system->user_id !== auth()->user()->id, 403);
        return view('user.pages.systems.edit', compact('system'));
    }

    public function update(Request $request, System $system)
    {
        abort_if($system->user_id !== auth()->user()->id, 403);
        // dd($request->all());
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'repository_url' => 'required|string|max:255|url',
            'description' => 'nullable|string',
            'category' => 'required|string|in:internal,eksternal',
        ]);
        try {
            $this->systemService->updateSystem($system, $validatedData);
            return redirect()->route('systems')->with('success', 'Data sistem berhasil diperbarui!');
        } catch (Exception $e) {
            Log::error("Gagal mengupdate sistem (ID: {$system->id}): " . $e->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan saat mencoba memperbarui sistem. Silakan coba lagi.')
                ->withInput();
        }
    }

    public function destroy(System $system)
    {
        abort_if($system->user_id !== auth()->user()->id, 403);

        try {
            $this->systemService->deleteSystem($system);
            return redirect()->route('systems')->with('success', 'Sistem berhasil dihapus!');
        } catch (Exception $e) {
            Log::error("Gagal menghapus sistem (ID: {$system->id}): " . $e->getMessage());

            return redirect()->route('systems')
                ->with('error', 'Terjadi kesalahan saat mencoba menghapus sistem. Silakan coba lagi.');
        }
    }
}
