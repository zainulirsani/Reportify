<?php

namespace App\Models;

use App\Models\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CodeSnippet extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'language',
        'content',
        'description',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
