<?php

namespace App\Services\Finance;

use Illuminate\Support\Carbon;

/**
 * Ministry highlights for a period, shared with givers and stakeholders.
 */
final readonly class ImpactSummary
{
    /**
     * @param  array<string, int>  $decisions  decision label => souls
     */
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public int $missionsServiced,
        public int $studentsReached,
        public int $souls,
        public array $decisions,
        public int $inDiscipleship,
        public int $incomeReceived,
    ) {}

    public function periodLabel(): string
    {
        return $this->from->isSameMonth($this->to)
            ? $this->from->format('F Y')
            : $this->from->format('j M Y') . ' – ' . $this->to->format('j M Y');
    }

    /**
     * One-line highlights for short channels (SMS, WhatsApp).
     */
    public function headline(): string
    {
        return sprintf(
            '%s: %s missions, %s students reached, %s souls won.',
            $this->periodLabel(),
            number_format($this->missionsServiced),
            number_format($this->studentsReached),
            number_format($this->souls),
        );
    }
}
