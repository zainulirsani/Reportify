<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\System;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService
{
    /**
     * Mengambil daftar tugas milik user dengan paginasi.
     */
    public function getTasksForUser(User $user): LengthAwarePaginator
    {
        return $user->tasks()->with('system')->latest()->paginate(10);
    }

    /**
     * Membuat tugas baru, menangani upload file, dan memberinya task_code unik.
     */
    public function createNewTask(array $validatedData, User $user): Task
    {
        // --- LOGIKA UPLOAD FILE ---
        if (!empty($validatedData['attachment'])) {
            $validatedData['attachment_path'] = $validatedData['attachment']->store('task_attachments', 'public');
        }

        // --- LOGIKA PEMBUATAN TASK CODE ---
        $system = System::findOrFail($validatedData['system_id']);
        $prefix = $this->generatePrefixFromName($system->name);

        // Tambahkan tahun-bulan sebagai bagian kode
        $dateCode = now()->format('Ym'); // contoh: 202509

        // Cari nomor urut terakhir untuk sistem & bulan ini
        $lastTask = Task::where('system_id', $system->id)
            ->where('task_code', 'like', $prefix . '-' . $dateCode . '%')
            ->latest('id')
            ->first();

        $nextNumber = 1;
        if ($lastTask && $lastTask->task_code) {
            $lastNumber = (int) substr($lastTask->task_code, -4);
            $nextNumber = $lastNumber + 1;
        }

        // Task code unik: PREFIX-YYYYMM-0001
        $validatedData['task_code'] = sprintf("%s-%s-%04d", $prefix, $dateCode, $nextNumber);

        return $user->tasks()->create($validatedData);
    }

    /**
     * Mengupdate data tugas yang sudah ada, termasuk menangani file.
     */
    public function updateTask(Task $task, array $validatedData): bool
    {
        // --- LOGIKA UPLOAD FILE (Update) ---
        if (!empty($validatedData['attachment'])) {
            // Hapus file lampiran lama jika ada, untuk menghemat ruang
            if ($task->attachment_path) {
                Storage::disk('public')->delete($task->attachment_path);
            }
            // Simpan file yang baru dan catat path-nya
            $validatedData['attachment_path'] = $validatedData['attachment']->store('task_attachments', 'public');
        }

        return $task->update($validatedData);
    }

    /**
     * Menghapus data tugas.
     */
    public function deleteTask(Task $task): ?bool
    {
        // Hapus juga file lampiran terkait jika ada
        if ($task->attachment_path) {
            Storage::disk('public')->delete($task->attachment_path);
        }
        return $task->delete();
    }

    /**
     * Helper method untuk membuat prefix dari nama
     */
    private function generatePrefixFromName(string $name): string
    {
        $words = preg_split("/\s+/", $name);
        $acronym = "";
        foreach ($words as $w) {
            $acronym .= mb_substr($w, 0, 1);
        }

        if (strlen($acronym) === 1) {
            $acronym = mb_substr($name, 0, 3);
        }

        return strtoupper($acronym);
    }

    public function getOpenTasksForUser(User $user): Collection
    {
        return $user->tasks()
            ->whereIn('status', ['todo', 'in_progress'])
            ->with('system:id,name') // Ambil nama sistem untuk info tambahan
            ->latest()
            ->get();
    }
}
