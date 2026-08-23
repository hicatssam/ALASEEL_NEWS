<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name','email','password','avatar','phone','photo','status','last_login_at'];

    protected $hidden   = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'status'            => 'boolean',
        'password'          => 'hashed',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function articles(): HasMany  { return $this->hasMany(Article::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function activityLogs(): HasMany  { return $this->hasMany(ActivityLog::class); }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->roles()
            ->where('roles.status', true)
            ->whereHas('permissions', fn ($query) => $query->where('permissions.slug', $slug))
            ->exists();
    }

    public function hasAnyPermission(array $slugs): bool
    {
        return collect($slugs)->contains(fn ($slug) => $this->hasPermission($slug));
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super-admin');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $photo = $this->photo ?: $this->avatar;

        if (blank($photo)) {
            return null;
        }

        if (filter_var($photo, FILTER_VALIDATE_URL) || str_starts_with($photo, '//')) {
            return $photo;
        }

        $path = str_replace('\\', '/', trim($photo));
        $path = preg_replace('#^/?storage/app/public/#', '', $path);
        $path = preg_replace('#^/?public/#', '', $path);
        $path = preg_replace('#^/?storage/#', '', $path);

        return route('site.media', ['path' => ltrim((string) $path, '/')]);
    }



    public function scopeActive($query) { return $query->where('status', true); }
}
