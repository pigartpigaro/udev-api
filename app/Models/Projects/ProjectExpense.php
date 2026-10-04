<?php

namespace App\Models\Projects;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToWorkspace;

class ProjectExpense extends Model
{
    use BelongsToWorkspace;

    public const CATEGORIES = ['transport', 'accommodation', 'meals', 'equipment', 'software', 'communication', 'other'];

    protected $fillable = ['project_id', 'category', 'paid_to', 'amount', 'spent_at', 'method', 'reference', 'notes', 'status', 'recorded_by', 'void_reason', 'voided_at', 'voided_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'spent_at' => 'date:Y-m-d', 'voided_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(static function (self $expense): void {
            $expense->forceFill(['code' => 'U-PG-'.$expense->created_at->format('Y').'-'.str_pad((string) $expense->getKey(), 6, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function voider(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
}
