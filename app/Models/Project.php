<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name', 'description',
        'db_host', 'db_port', 'db_database', 'db_username', 'db_password',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(ProcessTask::class)->orderBy('id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(Transition::class);
    }

    public function dbTables(): HasMany
    {
        return $this->hasMany(DbTable::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(ProcessCase::class)->latest();
    }
}
