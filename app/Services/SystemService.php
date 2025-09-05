<?php

namespace App\Services;

use App\Models\System;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SystemService
{
    /**
     * Mengambil semua data sistem milik user dengan paginasi.
     *
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function getSystemsForUser(User $user): LengthAwarePaginator
    {
        return $user->systems()->latest()->paginate(10);
    }

    /**
     * Membuat data sistem baru.
     *
     * @param array $validatedData Data yang sudah divalidasi dari request.
     * @param User $user User yang sedang login.
     * @return System
     */
    public function createNewSystem(array $validatedData, User $user): System
    {
        return $user->systems()->create($validatedData);
    }

    /**
     * Mengupdate data sistem yang sudah ada.
     *
     * @param System $system Model sistem yang akan diupdate.
     * @param array $validatedData Data yang sudah divalidasi dari request.
     * @return bool
     */
    public function updateSystem(System $system, array $validatedData): bool
    {
        return $system->update($validatedData);
    }

    /**
     * Menghapus data sistem.
     *
     * @param System $system Model sistem yang akan dihapus.
     * @return bool|null
     */
    public function deleteSystem(System $system): ?bool
    {
        return $system->delete();
    }

    public function getAllSystemsForUser(User $user): Collection
    {
        return $user->systems()->orderBy('name')->get();
    }
}