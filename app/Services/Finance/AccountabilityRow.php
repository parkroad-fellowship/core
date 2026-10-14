<?php

namespace App\Services\Finance;

use App\Enums\PRFReconciliationStatus;
use App\Models\AccountingEvent;

/**
 * One line of the monthly accountability sheet: what an accounting event received, really spent,
 * collected as tokens and refunded. A fully accounted event has a zero balance.
 */
final readonly class AccountabilityRow
{
    /**
     * @param  array<int, int>  $expenses  expense category id => amount spent (excluding transaction costs)
     */
    public function __construct(
        public AccountingEvent $event,
        public int $disbursed,
        public array $expenses,
        public int $transactionCosts,
        public int $tokens,
        public int $refunded,
    ) {}

    public function totalExpenses(): int
    {
        return array_sum($this->expenses) + $this->transactionCosts;
    }

    /**
     * Surplus to be refunded (negative when the missioner spent more than they received).
     */
    public function toRefund(): int
    {
        return $this->disbursed - $this->totalExpenses() + $this->tokens;
    }

    public function balance(): int
    {
        return $this->toRefund() - $this->refunded;
    }

    public function hasActivity(): bool
    {
        return $this->disbursed > 0 || $this->totalExpenses() > 0 || $this->tokens > 0 || $this->refunded > 0;
    }

    /**
     * What the numbers say. The treasurer's own verdict (on the event) takes precedence.
     */
    public function suggestedStatus(): PRFReconciliationStatus
    {
        if (!$this->hasActivity()) {
            return PRFReconciliationStatus::PENDING;
        }

        if ($this->balance() === 0 && ($this->totalExpenses() > 0 || $this->disbursed === 0)) {
            return PRFReconciliationStatus::FULLY_ACCOUNTED;
        }

        return PRFReconciliationStatus::NEEDS_ATTENTION;
    }

    public function status(): PRFReconciliationStatus
    {
        return $this->event->reconciliation_status === PRFReconciliationStatus::PENDING
            ? $this->suggestedStatus()
            : $this->event->reconciliation_status;
    }

    /**
     * Why the row needs attention, in the treasurer's words when they gave some.
     */
    public function remarks(): string
    {
        if (filled($this->event->reconciliation_remarks)) {
            return (string) $this->event->reconciliation_remarks;
        }

        return match (true) {
            !$this->hasActivity() => '',
            $this->disbursed > 0 && $this->totalExpenses() === 0 => 'Accounting for KES '
                . number_format($this->disbursed)
                . ' pending',
            $this->balance() > 0 => 'Refund of KES ' . number_format($this->balance()) . ' pending',
            $this->balance() < 0 => 'Overspent / over-refunded by KES ' . number_format(abs($this->balance())),
            default => 'Fully accounted',
        };
    }
}
