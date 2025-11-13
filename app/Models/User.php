<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relationships
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'uploaded_by');
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(UserWarning::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(UserBlock::class);
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(ModerationLog::class, 'moderator_id');
    }

    /**
     * Role checking methods
     */
    public function hasRole(string $roleName): bool
    {
        // Use roles relationship (Many-to-Many)
        return $this->roles()->where('slug', $roleName)->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPERADMIN);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN) || $this->isSuperAdmin();
    }

    public function isModerator(): bool
    {
        return $this->hasRole(Role::MODERATOR) || $this->isAdmin();
    }

    public function isEditor(): bool
    {
        return $this->hasRole(Role::EDITOR);
    }

    public function canManageContent(): bool
    {
        return $this->isAdmin() || $this->isModerator() || $this->isEditor();
    }

    public function canModerate(): bool
    {
        return $this->isModerator() || $this->isEditor();
    }

    /**
     * Block/Warning checking methods
     */
    public function isBlocked(): bool
    {
        return $this->blocks()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('block_type', 'permanent')
                    ->orWhere(function ($q) {
                        $q->where('block_type', 'temporary')
                          ->where('expires_at', '>', now());
                    });
            })
            ->exists();
    }

    public function getActiveWarnings()
    {
        return $this->warnings()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUnreadWarningsCount(): int
    {
        return $this->warnings()
            ->where('is_read', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    /**
     * Permission checking
     */
    public function hasPermission(string $permission): bool
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
}
