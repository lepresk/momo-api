<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Concerns;

use Lepresk\MomoApi\Models\ApiToken;

trait InteractsWithHttp
{
    protected function buildHeaders(ApiToken $token, array $additional = []): array
    {
        $headers = [
            'Ocp-Apim-Subscription-Key' => $this->getSubscriptionKey(),
            'X-Target-Environment' => $this->environment,
            'Authorization' => 'Bearer ' . $token->getAccessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if (!empty($this->config->getCallbackUri())) {
            $headers['X-Callback-Url'] = $this->config->getCallbackUri();
        }

        return array_merge($headers, $additional);
    }
}
