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
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('system_id')->constrained('systems')->onDelete('cascade');
            $table->text('summary_paragraph');
            $table->json('systems_worked_on')->nullable();
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->timestamps();

            // Menambahkan index untuk pencarian yang lebih cepat
            $table->index(['user_id', 'system_id', 'week_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_reports');
    }
};
