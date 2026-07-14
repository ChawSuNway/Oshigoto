<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseNumber extends Model
{
    protected $fillable = [
        'system_id',
        'code',
        'description',
    ];

    /** The system this case number belongs to. */
    public function system(): BelongsTo
    {
        return $this->belongsTo(SystemId::class, 'system_id');
    }
}
