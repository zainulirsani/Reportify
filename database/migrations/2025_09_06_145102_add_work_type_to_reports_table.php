<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Tambahkan kolom baru setelah 'status'
            // 'normal' akan menjadi nilai default saat laporan dibuat
            $table->enum('work_type', ['normal', 'overtime'])->default('normal')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('work_type');
        });
    }
};
