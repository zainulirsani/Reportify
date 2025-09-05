<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\System;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Hapus user lama jika ada (opsional, baik untuk testing)
        User::query()->delete();

        // Buat satu user utama untuk kita login
        User::factory()
            ->has(
                // Buat 3 sistem untuk user ini
                System::factory()->count(3)
                    ->has(
                        // Untuk SETIAP sistem, buat 20 laporan
                        Report::factory()->count(20)
                    )
            )
            ->create([
                'name' => 'Muhammad Zainul Irsani',
                'email' => 'zainulirsani64@gmail.com',
                'password' => bcrypt('Yersan131002'),
            ]);
    }
}