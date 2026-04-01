<?php

namespace App\Limitless\Exception;

final class RateLimitException extends LimitlessTcgException
{
    public static function create(): self
    {
        return new self('Rate limit exceeded (HTTP 429). Please try again later.', 429);
    }
}
