<?php

namespace App\Enums;

enum PRFMpesaTransactionType: int
{
    case DEFAULT = 1;
    case OTHER_REGISTERED_USER = 2;
    case AGENT_WITHDRAWAL = 3;
    case ATM_WITHDRAWAL = 4;

    public static function getOptions(): array
    {
        return [
            self::DEFAULT->value => 'Default',
            self::OTHER_REGISTERED_USER->value => 'Other Registered User',
            self::AGENT_WITHDRAWAL->value => 'Agent Withdrawal',
            self::ATM_WITHDRAWAL->value => 'ATM Withdrawal',
        ];
    }

    public function getElements(): array
    {
        return [
            self::DEFAULT->value,
            self::OTHER_REGISTERED_USER->value,
            self::AGENT_WITHDRAWAL->value,
            self::ATM_WITHDRAWAL->value,
        ];
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::DEFAULT->value => self::DEFAULT,
            self::OTHER_REGISTERED_USER->value => self::OTHER_REGISTERED_USER,
            self::AGENT_WITHDRAWAL->value => self::AGENT_WITHDRAWAL,
            self::ATM_WITHDRAWAL->value => self::ATM_WITHDRAWAL,
        };
    }
}
