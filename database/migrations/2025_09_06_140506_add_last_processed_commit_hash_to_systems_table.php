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
        Schema::table('systems', function (Blueprint $table) {
            $table->string('last_processed_commit_hash')->nullable()->after('repository_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn('last_processed_commit_hash');
        });
    }
};
