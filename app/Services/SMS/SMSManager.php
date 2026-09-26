<?php

namespace App\Services\SMS;

use App\Contracts\Services\SMSGatewayInterface;
use App\Enums\PRFIntegration;
use App\Enums\PRFSMSStatus;
use App\Services\Tenancy\TenantIntegrations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Manager;

/**
 * Resolves the tenant's SMS provider. Add a provider with a createXDriver() method
 * or SMSManager::extend('name', fn () => new XSMSGateway()).
 */
class SMSManager extends Manager implements SMSGatewayInterface
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('prf.sms.default', 'advanta');
    }

    public function createAdvantaDriver(): SMSGatewayInterface
    {
        return new AdvantaSMSGateway();
    }

    public function createAfricasTalkingDriver(): SMSGatewayInterface
    {
        return new AfricasTalkingSMSGateway();
    }

    public function send(string $phoneNumber, string $message, ?Model $smsLoggable = null): SMSResult
    {
        $this->container->make(TenantIntegrations::class)->require(PRFIntegration::SMS);

        return $this->gateway()->send($phoneNumber, $message, $smsLoggable);
    }

    public function deliveryStatus(string $messageId): PRFSMSStatus
    {
        $this->container->make(TenantIntegrations::class)->require(PRFIntegration::SMS);

        return $this->gateway()->deliveryStatus($messageId);
    }

    private function gateway(): SMSGatewayInterface
    {
        $driver = $this->driver();

        if (!$driver instanceof SMSGatewayInterface) {
            throw new \LogicException('SMS drivers must implement ' . SMSGatewayInterface::class . '.');
        }

        return $driver;
    }
}
