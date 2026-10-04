<?php

namespace App\Models\Finance;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamLoanRepayment extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['team_loan_id', 'amount', 'repaid_at', 'method', 'reference', 'notes', 'status', 'recorded_by', 'void_reason', 'voided_at', 'voided_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'repaid_at' => 'date:Y-m-d', 'voided_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(static function (self $repayment): void {
            $repayment->forceFill(['code' => 'U-KB-'.$repayment->repaid_at->format('Y').'-'.str_pad((string) $repayment->getKey(), 6, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function loan(): BelongsTo { return $this->belongsTo(TeamLoan::class, 'team_loan_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function voider(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
}
