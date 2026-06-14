<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DbColumn extends Model
{
    protected $fillable = [
        'db_table_id', 'name', 'type', 'length', 'nullable',
        'default', 'is_primary', 'auto_increment', 'sort_order',
    ];

    protected $casts = [
        'nullable' => 'boolean',
        'is_primary' => 'boolean',
        'auto_increment' => 'boolean',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(DbTable::class, 'db_table_id');
    }
}
