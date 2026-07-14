<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'japanese_name',
        'department_name',
        'email',
        'password',
        'role',
        'manager_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The manager this user reports to.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Employees that report to this user (when the user is a manager).
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Reports authored by this user.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Reports addressed to this user (as a manager).
     */
    public function receivedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'manager_id');
    }

    /**
     * Late-coming notices authored by this user.
     */
    public function lateComings(): HasMany
    {
        return $this->hasMany(LateComing::class);
    }

    /**
     * Early-leave applications authored by this user.
     */
    public function earlyLeaves(): HasMany
    {
        return $this->hasMany(EarlyLeave::class);
    }

    /**
     * Half-day-leave applications authored by this user.
     */
    public function halfDayLeaves(): HasMany
    {
        return $this->hasMany(HalfDayLeave::class);
    }

    /**
     * Full-day leave applications authored by this user.
     */
    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
