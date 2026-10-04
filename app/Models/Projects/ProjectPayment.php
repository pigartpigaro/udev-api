<?php

namespace App\Models\Projects;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToWorkspace;

class ProjectPayment extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['invoice_id', 'payment_type', 'amount', 'received_at', 'method', 'reference', 'notes', 'status', 'received_by', 'void_reason', 'voided_at', 'voided_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'received_at' => 'date:Y-m-d', 'voided_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(static function (self $payment): void {
            $payment->forceFill(['code' => 'U-PY-'.$payment->created_at->format('Y').'-'.str_pad((string) $payment->getKey(), 6, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function invoice(): BelongsTo { return $this->belongsTo(ProjectInvoice::class, 'invoice_id'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function voider(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
}
