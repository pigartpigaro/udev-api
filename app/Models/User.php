<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_platform_admin',
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
            'is_platform_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (filled($user->username)) {
                $user->username = strtolower(trim($user->username));
                return;
            }

            $localPart = strtolower((string) strstr((string) $user->email, '@', true));
            $base = trim((string) preg_replace('/[^a-z0-9._-]+/', '', $localPart), '._-');
            $base = substr($base !== '' ? $base : 'user', 0, 24);
            $username = $base;
            $suffix = 1;

            while (static::query()->where('username', $username)->exists()) {
                $username = substr($base, 0, 24).$suffix;
                $suffix++;
            }

            $user->username = $username;
        });

        static::created(function (self $user): void {
            if (! DB::getSchemaBuilder()->hasTable('workspace_user') || ! DB::getSchemaBuilder()->hasTable('workspaces')) {
                return;
            }

            $workspaceId = DB::table('workspaces')->where('slug', 'workspace-utama')->value('id');
            if ($workspaceId === null) {
                return;
            }

            DB::table('workspace_user')->insertOrIgnore([
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'role' => $user->role ?: 'member',
                'is_owner' => $user->role === 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class)->withPivot(['role', 'is_owner'])->withTimestamps();
    }
}
