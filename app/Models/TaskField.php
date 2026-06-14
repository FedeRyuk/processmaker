<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskField extends Model
{
    protected $fillable = [
        'task_id', 'name', 'label', 'type', 'options', 'config',
        'read_db', 'read_table', 'read_column', 'default_value',
        'write_db', 'write_table', 'write_column', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'config' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProcessTask::class, 'task_id');
    }
}
