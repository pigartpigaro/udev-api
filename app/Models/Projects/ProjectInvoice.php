<?php

namespace App\Models\Projects;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToWorkspace;

class ProjectInvoice extends Model
{
    use BelongsToWorkspace;

    public const STATUSES = ['draft', 'issued', 'cancelled'];
    public const BILLING_CYCLES = ['monthly', 'one_time'];

    protected $attributes = ['status' => 'draft', 'billing_cycle' => 'monthly'];

    protected $fillable = ['project_id', 'billing_cycle', 'billing_period', 'supersedes_invoice_id', 'amount', 'issued_at', 'due_at', 'notes', 'status', 'cancellation_reason', 'cancelled_at', 'cancelled_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'issued_at' => 'date:Y-m-d', 'due_at' => 'date:Y-m-d', 'cancelled_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(static function (self $invoice): void {
            $invoice->forceFill([
                'code' => 'U-IN-'.$invoice->created_at->format('Y').'-'.str_pad((string) $invoice->getKey(), 4, '0', STR_PAD_LEFT),
            ])->saveQuietly();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_invoice_id');
    }

    public function replacement(): HasOne
    {
        return $this->hasOne(self::class, 'supersedes_invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProjectPayment::class, 'invoice_id');
    }
}
