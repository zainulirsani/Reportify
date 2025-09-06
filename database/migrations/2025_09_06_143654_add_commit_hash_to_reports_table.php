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
            // Menambahkan kolom baru 'commit_hash' setelah kolom 'status'
            // ->nullable() agar tidak error pada data lama yang mungkin kosong
            // ->index() untuk mempercepat pencarian di kolom ini
            $table->string('commit_hash')->nullable()->after('status')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('reports', function (Blueprint $table) {
            // Perintah untuk menghapus kolom jika migrasi di-rollback
            $table->dropColumn('commit_hash');
        });
    }
};
