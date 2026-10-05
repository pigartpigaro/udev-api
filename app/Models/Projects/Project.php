<?php

namespace App\Models\Projects;

use App\Models\Master\Customer;
use App\Models\Master\ProjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToWorkspace;

class Project extends Model
{
    use BelongsToWorkspace;

    public const STATUSES = ['draft', 'active', 'on_hold', 'completed', 'cancelled'];

    protected $fillable = [
        'name',
        'customer_id',
        'project_type_id',
        'start_date',
        'target_end_date',
        'status',
        'description',
        'application_url',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'target_end_date' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::created(static function (self $project): void {
            $year = $project->created_at?->format('Y') ?? now()->format('Y');
            $project->forceFill([
                'code' => 'U-PR-'.$year.'-'.str_pad((string) $project->getKey(), 4, '0', STR_PAD_LEFT),
            ])->saveQuietly();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }
}
