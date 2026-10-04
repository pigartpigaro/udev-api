<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToWorkspace;

class Customer extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::created(static function (self $customer): void {
            $customer->forceFill([
                'code' => 'U-PL'.str_pad((string) $customer->getKey(), 4, '0', STR_PAD_LEFT),
            ])->saveQuietly();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
