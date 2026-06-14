<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessTask extends Model
{
    protected $fillable = [
        'project_id', 'name', 'description', 'is_initial', 'pos_x', 'pos_y',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(TaskField::class, 'task_id')->orderBy('sort_order');
    }

    public function outgoing(): HasMany
    {
        return $this->hasMany(Transition::class, 'from_task_id');
    }

    public function incoming(): HasMany
    {
        return $this->hasMany(Transition::class, 'to_task_id');
    }
}
