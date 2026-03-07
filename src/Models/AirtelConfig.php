<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Models;

class AirtelConfig
{
    private string $clientId;
    private string $clientSecret;
    private string $encryptedPin;
    private string $country;
    private string $currency;
    private string $callbackUri;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $encryptedPin = '',
        string $country = 'CG',
        string $currency = 'XAF',
        string $callbackUri = ''
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->encryptedPin = $encryptedPin;
        $this->country = $country;
        $this->currency = $currency;
        $this->callbackUri = $callbackUri;
    }

    public static function collection(
        string $clientId,
        string $clientSecret,
        string $callbackUri = '',
        string $country = 'CG',
        string $currency = 'XAF'
    ): self {
        return new self($clientId, $clientSecret, '', $country, $currency, $callbackUri);
    }

    public static function disbursement(
        string $clientId,
        string $clientSecret,
        string $encryptedPin,
        string $callbackUri = '',
        string $country = 'CG',
        string $currency = 'XAF'
    ): self {
        return new self($clientId, $clientSecret, $encryptedPin, $country, $currency, $callbackUri);
    }

    public function getClientId(): string { return $this->clientId; }
    public function getClientSecret(): string { return $this->clientSecret; }
    public function getEncryptedPin(): string { return $this->encryptedPin; }
    public function getCountry(): string { return $this->country; }
    public function getCurrency(): string { return $this->currency; }
    public function getCallbackUri(): string { return $this->callbackUri; }
}
