<?php

namespace App\Models;

use App\Models\CodeSnippet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_id',
        'title',
        'description',
        'raw_diff',
        'status',
        'work_type', // <-- Tambahkan ini
        'started_at',
        'completed_at',
        'raw_github_payload',
        'commit_hash',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'row_github_payload' => 'array',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function codeSnippets(): HasMany
    {
        return $this->hasMany(CodeSnippet::class);
    }
}
