<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transition extends Model
{
    protected $fillable = [
        'project_id', 'from_task_id', 'to_task_id', 'conditions', 'label',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    public function fromTask(): BelongsTo
    {
        return $this->belongsTo(ProcessTask::class, 'from_task_id');
    }

    public function toTask(): BelongsTo
    {
        return $this->belongsTo(ProcessTask::class, 'to_task_id');
    }
}
