<?php

use App\Models\CentralSetting;

it('can set and get a string setting', function () {
    CentralSetting::set('app.name', 'Test App', 'app', 'string');

    expect(CentralSetting::get('app.name'))->toBe('Test App');
});

it('can set and get an array setting', function () {
    CentralSetting::set('admin_emails', ['a@b.com', 'c@d.com'], 'admin', 'array');

    expect(CentralSetting::get('admin_emails'))->toBe(['a@b.com', 'c@d.com']);
});

it('returns default when setting does not exist', function () {
    expect(CentralSetting::get('nonexistent', 'default'))->toBe('default');
});

it('can get admin emails as lowercase array', function () {
    CentralSetting::set('admin_emails', ['Admin@Example.COM', 'User@Test.ORG'], 'admin', 'array');

    $emails = CentralSetting::getAdminEmails();

    expect($emails)->toBe(['admin@example.com', 'user@test.org']);
});

it('returns empty array when admin emails not configured', function () {
    expect(CentralSetting::getAdminEmails())->toBe([]);
});

it('clears cache after setting a value', function () {
    CentralSetting::set('test.key', 'first', 'test', 'string');
    expect(CentralSetting::get('test.key'))->toBe('first');

    CentralSetting::set('test.key', 'second', 'test', 'string');
    expect(CentralSetting::get('test.key'))->toBe('second');
});

it('can cast boolean values', function () {
    CentralSetting::set('feature.enabled', 'true', 'feature', 'boolean');

    expect(CentralSetting::get('feature.enabled'))->toBeTrue();
});

it('can cast integer values', function () {
    CentralSetting::set('limits.max_users', '50', 'limits', 'integer');

    expect(CentralSetting::get('limits.max_users'))->toBe(50);
});

it('can delete a setting', function () {
    CentralSetting::set('temp.key', 'value', 'temp', 'string');
    expect(CentralSetting::get('temp.key'))->toBe('value');

    CentralSetting::where('key', 'temp.key')->delete();
    CentralSetting::clearCache();

    expect(CentralSetting::get('temp.key'))->toBeNull();
});
