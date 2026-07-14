<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemId extends Model
{
    protected $fillable = [
        'code',
        'description',
    ];

    /** Case numbers that belong to this system. */
    public function caseNumbers(): HasMany
    {
        return $this->hasMany(CaseNumber::class, 'system_id');
    }
}
