<?php
declare(strict_types=1);

namespace Lepresk\MomoApi\Exceptions;

use Symfony\Contracts\HttpClient\ResponseInterface;

class ExceptionFactory
{
    public static function create(ResponseInterface $response): MomoException
    {
        $content = $response->toArray(false);

        switch ($response->getStatusCode()) {
            case 400:
                return new BadRequestExeption();
            case 401:
                return new InvalidSubscriptionKeyException($content['message'] ?? null);
            case 404:
                return new RessourceNotFoundException($content['message'] ?? '', $response->getStatusCode());
            case 409:
                return new ConflictException($content['message'] ?? null);
            case 500:
                return new InternalServerErrorException($content['message'] ?? null);
            default:
                return new MomoException("Operation failled with status : " . $response->getStatusCode() . " | Content : " . $response->getContent(false));
        }
    }
}