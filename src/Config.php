<?php
declare(strict_types=1);

namespace Lepresk\MomoApi;

class Config
{
    private string $subscriptionKey;

    private string $apiUser;

    private string $apiKey;

    private string $callbackUri;

    /**
     * @param string $subscriptionKey
     * @param string $apiUser
     * @param string $apiKey
     * @param string $callbackUri
     */
    private function __construct(string $subscriptionKey, string $apiUser = '', string $apiKey = '', string $callbackUri = '')
    {
        $this->subscriptionKey = $subscriptionKey;
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
        $this->callbackUri = $callbackUri;
    }

    public static function sandbox(string $subscriptionKey): Config
    {
        return new self($subscriptionKey);
    }

    public static function collection(string $subscriptionKey, string $apiUser, string $apiKey, string $callbackUri): Config
    {
        return new self($subscriptionKey, $apiUser, $apiKey, $callbackUri);
    }

    public static function disbursement(string $subscriptionKey, string $apiUser, string $apiKey, string $callbackUri): Config
    {
        return new self($subscriptionKey, $apiUser, $apiKey, $callbackUri);
    }

    /**
     * @return string
     */
    public function getSubscriptionKey(): string
    {
        return $this->subscriptionKey;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getApiUser(): string
    {
        return $this->apiUser;
    }

    /**
     * @return string
     */
    public function getCallbackUri(): string
    {
        return $this->callbackUri;
    }

    /**
     * @param string $callbackUri
     * @return Config
     */
    public function setCallbackUri(string $callbackUri): Config
    {
        $this->callbackUri = $callbackUri;
        return $this;
    }
}