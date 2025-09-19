<?php

// PERBAIKAN 1: Namespace yang benar
namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\SystemService;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // PERBAIKAN 2: Import Auth Facade

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService,
        protected SystemService $systemService
    ) {}

    public function index()
    {
        // PERBAIKAN 3: Menggunakan Auth::user()
        $user = Auth::user();
        $tasks = $this->taskService->getTasksForUser($user);
        $systems = $this->systemService->getAllSystemsForUser($user);

        return view('user.pages.tasks', compact('tasks', 'systems'));
    }

    public function show(Task $task)
    {
        // Pastikan user hanya bisa melihat tugas miliknya
        abort_if($task->user_id !== Auth::id(), 403);

        // Kirim data tugas beserta relasi 'system' sebagai JSON
        return response()->json($task->load(['system', 'reports.codeSnippets']));
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'system_id' => 'required|exists:systems,id',
            'description' => 'nullable|string',
            'status' => 'required|in:todo,in_progress,done',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            // PERBAIKAN 3: Menggunakan Auth::user()
            $this->taskService->createNewTask($validatedData, Auth::user());
            return redirect()->route('tasks.index')->with('success', 'Tugas baru berhasil ditambahkan!');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan tugas baru: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencoba menyimpan tugas.')->withInput();
        }
    }

    public function update(Request $request, Task $task)
    {
        // PERBAIKAN 3: Menggunakan Auth::user()
        abort_if($task->user_id !== Auth::user()->id, 403);

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'system_id' => 'required|exists:systems,id',
            'description' => 'nullable|string',
            'status' => 'required|in:todo,in_progress,done',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            $this->taskService->updateTask($task, $validatedData);
            return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui!');
        } catch (\Exception $e) {
            Log::error("Gagal mengupdate tugas (ID: {$task->id}): " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencoba memperbarui tugas.')->withInput();
        }
    }

    public function destroy(Task $task)
    {
        // PERBAIKAN 3: Menggunakan Auth::user()
        abort_if($task->user_id !== Auth::user()->id, 403);

        try {
            $this->taskService->deleteTask($task);
            return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error("Gagal menghapus tugas (ID: {$task->id}): " . $e->getMessage());
            return redirect()->route('tasks.index')->with('error', 'Terjadi kesalahan saat mencoba menghapus tugas.');
        }
    }

    public function create() { abort(404); }
    public function edit(Task $task) { abort(404); }
}