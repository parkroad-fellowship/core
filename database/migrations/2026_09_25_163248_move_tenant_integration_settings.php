<?php

use App\Enums\PRFIntegration;
use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Moves tenant integration settings to the keys declared in PRFIntegration and encrypts
 * the secret ones. Gemini settings become the provider-agnostic `ai.*` settings.
 */
return new class extends Migration {
    /**
     * @var array<string, string>
     */
    private const RENAMES = [
        'sms.default' => 'sms.driver',
        'africas_talking.username' => 'sms.africas_talking_username',
        'africas_talking.api_key' => 'sms.africas_talking_api_key',
        'africas_talking.from' => 'sms.africas_talking_from',
        'gemini.api_key' => 'ai.api_key',
        'gemini.model' => 'ai.model',
        'gemini.max_output_tokens' => 'ai.max_output_tokens',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::RENAMES as $from => $to) {
                $this->renameKey($from, $to);
            }

            // Gemini was the only provider before settings became provider-agnostic.
            $tenantsWithAiKey = DB::table('app_settings')
                ->where('key', 'ai.api_key')
                ->where('value', '!=', '')
                ->pluck('tenant_id');

            foreach ($tenantsWithAiKey as $tenantId) {
                DB::table('app_settings')->updateOrInsert(['tenant_id' => $tenantId, 'key' => 'ai.provider'], [
                    'group' => 'ai',
                    'value' => 'gemini',
                    'type' => 'string',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('app_settings')
                ->where('key', 'ai.model')
                ->where('value', 'like', 'models/%')
                ->update(['value' => DB::raw('substr(value, 8)')]);

            $this->encryptSecrets();

            // Replaced by a per-member temporary password generated when the Workspace account is created.
            DB::table('app_settings')->where('key', 'organization.google_workspace_temp_password')->delete();
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('app_settings')
                ->where('value', 'like', AppSetting::ENCRYPTED_PREFIX . '%')
                ->orderBy('id')
                ->each(function (object $setting): void {
                    DB::table('app_settings')
                        ->where('id', $setting->id)
                        ->update([
                            'value' => Crypt::decryptString(substr(
                                $setting->value,
                                strlen(AppSetting::ENCRYPTED_PREFIX),
                            )),
                        ]);
                });

            DB::table('app_settings')->where('key', 'ai.provider')->delete();

            foreach (array_reverse(self::RENAMES, true) as $from => $to) {
                $this->renameKey($to, $from);
            }
        });
    }

    private function renameKey(string $from, string $to): void
    {
        $group = explode('.', $to)[0];

        DB::table('app_settings')
            ->where('key', $from)
            ->orderBy('id')
            ->each(function (object $setting) use ($to, $group): void {
                $alreadyExists = DB::table('app_settings')
                    ->where('tenant_id', $setting->tenant_id)
                    ->where('key', $to)
                    ->exists();

                if ($alreadyExists) {
                    DB::table('app_settings')->where('id', $setting->id)->delete();

                    return;
                }

                DB::table('app_settings')->where('id', $setting->id)->update(['key' => $to, 'group' => $group]);
            });
    }

    private function encryptSecrets(): void
    {
        $secretKeys = collect(PRFIntegration::cases())
            ->flatMap(fn(PRFIntegration $integration) => $integration->secretSettings());

        DB::table('app_settings')
            ->whereIn('key', $secretKeys)
            ->where('value', '!=', '')
            ->where('value', 'not like', AppSetting::ENCRYPTED_PREFIX . '%')
            ->orderBy('id')
            ->each(function (object $setting): void {
                DB::table('app_settings')
                    ->where('id', $setting->id)
                    ->update([
                        'value' => AppSetting::ENCRYPTED_PREFIX . Crypt::encryptString($setting->value),
                    ]);
            });
    }
};
