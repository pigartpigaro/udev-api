<?php

namespace App\Models\Finance;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamLoan extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['borrower_id', 'amount', 'issued_at', 'due_at', 'purpose', 'method', 'reference', 'notes', 'status', 'recorded_by', 'void_reason', 'voided_at', 'voided_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'issued_at' => 'date:Y-m-d', 'due_at' => 'date:Y-m-d', 'voided_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(static function (self $loan): void {
            $loan->forceFill(['code' => 'U-KS-'.$loan->issued_at->format('Y').'-'.str_pad((string) $loan->getKey(), 6, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function borrower(): BelongsTo { return $this->belongsTo(User::class, 'borrower_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function voider(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
    public function repayments(): HasMany { return $this->hasMany(TeamLoanRepayment::class); }
}
