<?php
declare(strict_types=1);

namespace Lepresk\MomoApi;

class ApiToken
{
    private string $accessToken;
    private string $tokenType;
    private int $expiresIn;

    /**
     * @param string $accessToken
     * @param string $tokenType
     * @param int $expiresIn
     */
    public function __construct(string $accessToken, string $tokenType, int $expiresIn)
    {
        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
    }

    public static function fromArray(array $array): ApiToken
    {
        return new self($array['access_token'], $array['token_type'], $array['expires_in']);
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }
}