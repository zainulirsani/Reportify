<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'system_id',
        'summary_paragraph',
        'systems_worked_on',
        'week_start_date',
        'week_end_date',
    ];

    protected $casts = [
        'systems_worked_on' => 'array',
        'week_start_date' => 'date',
        'week_end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }
}