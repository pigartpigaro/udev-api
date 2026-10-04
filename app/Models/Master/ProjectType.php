<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToWorkspace;

class ProjectType extends Model
{
    use BelongsToWorkspace;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::created(static function (self $projectType): void {
            $projectType->forceFill([
                'code' => 'U-TP'.str_pad((string) $projectType->getKey(), 4, '0', STR_PAD_LEFT),
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
