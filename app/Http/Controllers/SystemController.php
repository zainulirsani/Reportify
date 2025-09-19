<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSyncCommits;
use App\Models\System;
use App\Services\SystemService;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    /**
     * Suntikkan semua service yang dibutuhkan melalui constructor.
     */
    public function __construct(
        protected SystemService $systemService,
        protected TaskService $taskService
    ) {}

    /**
     * Menampilkan halaman daftar sistem.
     */
    public function index()
    {
        $user = Auth::user();
        $systems = $this->systemService->getSystemsForUser($user);
        
        return view('user.pages.system', compact('systems'));
    }

    /**
     * Menyimpan sistem baru ke database.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:internal,external',
            'repository_url' => 'required|string|max:255|url',
            'description' => 'nullable|string',
        ]);

        try {
            $this->systemService->createNewSystem($validatedData, Auth::user());
            return redirect()->route('system')->with('success', 'Sistem baru berhasil ditambahkan!');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan sistem baru: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencoba menyimpan sistem.')->withInput();
        }
    }

    /**
     * Mengupdate data sistem di database.
     */
    public function update(Request $request, System $system)
    {
        abort_if($system->user_id !== Auth::id(), 403);
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:internal,external',
            'repository_url' => 'required|string|max:255|url',
            'description' => 'nullable|string',
        ]);
        
        try {
            $this->systemService->updateSystem($system, $validatedData);
            return redirect()->route('system')->with('success', 'Data sistem berhasil diperbarui!');
        } catch (\Exception $e) {
            Log::error("Gagal mengupdate sistem (ID: {$system->id}): " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencoba memperbarui sistem.')->withInput();
        }
    }

    /**
     * Menghapus sistem dari database.
     */
    public function destroy(System $system)
    {
        abort_if($system->user_id !== Auth::id(), 403);
        
        try {
            $this->systemService->deleteSystem($system);
            return redirect()->route('system')->with('success', 'Sistem berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error("Gagal menghapus sistem (ID: {$system->id}): " . $e->getMessage());
            return redirect()->route('system')->with('error', 'Terjadi kesalahan saat mencoba menghapus sistem.');
        }
    }

    /**
     * Mengambil pratinjau commit baru untuk ditampilkan di modal.
     */
    public function syncPreview(System $system)
    {
        abort_if($system->user_id !== Auth::id(), 403);
        
        try {
            $user = Auth::user();
            $newCommits = $this->systemService->getNewCommitsFromGitHub($system, $user);
            $openTasks = $this->taskService->getOpenTasksForUser($user);

            return response()->json([
                'new_commits' => array_values($newCommits),
                'open_tasks' => $openTasks,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil pratinjau sinkronisasi: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal terhubung dengan GitHub. Pastikan token dan URL repositori benar.'], 500);
        }
    }

    /**
     * Menerima pemetaan dari user dan mengirim tugas ke antrian (atau memproses langsung).
     */
    public function processSync(Request $request)
    {
        $validated = $request->validate([
            'system_id' => 'required|exists:systems,id',
            'mappings' => 'nullable|array',
            'mappings.*.commit' => 'required|string',
            'mappings.*.task_id' => 'nullable|exists:tasks,id',
        ]);
        
        $user = Auth::user();
        $system = System::findOrFail($validated['system_id']);

        abort_if($system->user_id !== $user->id, 403);
        
        $mappings = $validated['mappings'] ?? [];
        $processedMappings = [];

        foreach ($mappings as $mapping) {
            $mapping['commit'] = json_decode($mapping['commit'], true);
            $processedMappings[] = $mapping;
        }

        // PERBAIKAN DI SINI: Kirimkan ID, bukan seluruh objek
        ProcessSyncCommits::dispatch($processedMappings, $user->id, $system->id);

        return redirect()->route('systems')->with('success', "Proses sinkronisasi untuk '{$system->name}' telah dimulai. Laporan akan muncul dalam beberapa saat.");
    }
}