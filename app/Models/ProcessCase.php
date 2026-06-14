<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessCase extends Model
{
    protected $fillable = ['project_id', 'name', 'status'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function caseTasks(): HasMany
    {
        return $this->hasMany(CaseTask::class, 'case_id');
    }
}
