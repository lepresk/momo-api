<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Collection;

class Config
{
    private string $apiUser;
    private string $apiKey;
    private string $callbackUri;

    /**
     * @param string $apiUser
     * @param string $apiKey
     * @param string $callbackUri
     */
    public function __construct(string $apiUser, string $apiKey, string $callbackUri)
    {
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
        $this->callbackUri = $callbackUri;
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