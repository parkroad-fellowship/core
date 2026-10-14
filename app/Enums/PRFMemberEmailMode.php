<?php

namespace App\Enums;

/**
 * How a tenant's members sign in.
 */
enum PRFMemberEmailMode: int
{
    /** The tenant owns a Google Workspace domain; members get first.last@domain mailboxes. */
    case ORGANISATION_DOMAIN = 1;

    /** Members sign in with their own personal address (for example their Gmail). Nothing is generated. */
    case PERSONAL = 2;

    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ORGANISATION_DOMAIN => 'Organisation Google Workspace domain',
            self::PERSONAL => 'Personal email address',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ORGANISATION_DOMAIN => 'Members get an address on your Workspace domain, created automatically.',
            self::PERSONAL => 'Members sign in with the personal email address on their profile.',
        };
    }

    public static function fromValue(int|string|null $value): self
    {
        return self::tryFrom((int) $value) ?? self::PERSONAL;
    }
}
