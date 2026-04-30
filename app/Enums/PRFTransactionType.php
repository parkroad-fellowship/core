<?php

namespace App\Enums;

enum PRFTransactionType: int
{
    case MPESA_DEFAULT = 1;
    case MPESA_OTHER_REGISTERED_USER = 2;
    case MPESA_AGENT_WITHDRAWAL = 3;
    case MPESA_ATM_WITHDRAWAL = 4;
    case CASH = 5;
    case MPESA_PAYBILL_BUSINESS_TARRIFF = 6;

    public static function getOptions(): array
    {
        return [
            self::MPESA_DEFAULT->value => '(MPESA) User/Till/Paybill',
            self::MPESA_OTHER_REGISTERED_USER->value => '(MPESA) Other Registered User',
            self::MPESA_AGENT_WITHDRAWAL->value => '(MPESA) Agent Withdrawal',
            self::MPESA_ATM_WITHDRAWAL->value => '(MPESA) ATM Withdrawal',
            self::CASH->value => 'Cash',
            self::MPESA_PAYBILL_BUSINESS_TARRIFF->value => '(MPESA) PRF Paybill',
        ];
    }

    public function getElements(): array
    {
        return [
            self::MPESA_DEFAULT->value,
            self::MPESA_OTHER_REGISTERED_USER->value,
            self::MPESA_AGENT_WITHDRAWAL->value,
            self::MPESA_ATM_WITHDRAWAL->value,
            self::CASH->value,
            self::MPESA_PAYBILL_BUSINESS_TARRIFF->value,
        ];
    }

    public static function fromValue(int $value): self
    {
        return match ($value) {
            self::MPESA_DEFAULT->value => self::MPESA_DEFAULT,
            self::MPESA_OTHER_REGISTERED_USER->value => self::MPESA_OTHER_REGISTERED_USER,
            self::MPESA_AGENT_WITHDRAWAL->value => self::MPESA_AGENT_WITHDRAWAL,
            self::MPESA_ATM_WITHDRAWAL->value => self::MPESA_ATM_WITHDRAWAL,
            self::CASH->value => self::CASH,
            self::MPESA_PAYBILL_BUSINESS_TARRIFF->value => self::MPESA_PAYBILL_BUSINESS_TARRIFF,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MPESA_DEFAULT => '(MPESA) User/Till/Paybill',
            self::MPESA_OTHER_REGISTERED_USER => '(MPESA) Other Registered User',
            self::MPESA_AGENT_WITHDRAWAL => '(MPESA) Agent Withdrawal',
            self::MPESA_ATM_WITHDRAWAL => '(MPESA) ATM Withdrawal',
            self::CASH => 'Cash',
            self::MPESA_PAYBILL_BUSINESS_TARRIFF => '(MPESA) Business Tariff Paybill',
        };
    }
}
