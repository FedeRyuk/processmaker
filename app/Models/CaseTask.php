<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseTask extends Model
{
    protected $fillable = ['case_id', 'task_id', 'data', 'status', 'completed_at'];

    protected $casts = [
        'data' => 'array',
        'completed_at' => 'datetime',
    ];

    public function processCase(): BelongsTo
    {
        return $this->belongsTo(ProcessCase::class, 'case_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProcessTask::class, 'task_id');
    }

    public function isFrozen(): bool
    {
        return $this->status === 'completed';
    }
}
