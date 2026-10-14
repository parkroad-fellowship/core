<?php

namespace App\Enums;

/**
 * State of a member's Google Workspace mailbox (organisation-domain tenants only).
 */
enum PRFWorkspaceStatus: int
{
    case NOT_APPLICABLE = 0;
    case PENDING = 1;
    case PROVISIONED = 2;
    case FAILED = 3;
    case SUSPENDED = 4;

    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_APPLICABLE => 'Not applicable',
            self::PENDING => 'Pending',
            self::PROVISIONED => 'Provisioned',
            self::FAILED => 'Failed',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NOT_APPLICABLE => 'gray',
            self::PENDING => 'warning',
            self::PROVISIONED => 'success',
            self::FAILED => 'danger',
            self::SUSPENDED => 'gray',
        };
    }
}
