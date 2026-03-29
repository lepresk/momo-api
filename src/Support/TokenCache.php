<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Support;

class TokenCache
{
    private ?string $token = null;
    private int $expiresAt = 0;

    public function get(): ?string
    {
        if ($this->token === null || time() >= $this->expiresAt) {
            $this->token = null;
            return null;
        }
        return $this->token;
    }

    public function set(string $token, int $expiresIn): void
    {
        $this->token = $token;
        $this->expiresAt = time() + max(0, $expiresIn - 60);
    }
}
