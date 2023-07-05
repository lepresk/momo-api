<?php

namespace Lepresk\MomoApi\Models;

class AccountBalance
{
    private string $availableBalance;
    private string $currency;

    /**
     * @param string $availableBalance
     * @param string $currency
     */
    public function __construct(string $availableBalance, string $currency)
    {
        $this->availableBalance = $availableBalance;
        $this->currency = $currency;
    }

    public static function parse(array $array): AccountBalance
    {
        return new self(
            $array['availableBalance'],
            $array['currency'],
        );
    }

    /**
     * @return string
     */
    public function getAvailableBalance(): string
    {
        return $this->availableBalance;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

}