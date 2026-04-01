<?php

namespace App\Limitless\Exception;

class LimitlessTcgException extends \RuntimeException
{
    public static function fromHttpError(int $statusCode, string $message = ''): self
    {
        return new self(
            sprintf('Limitless TCG API error (HTTP %d): %s', $statusCode, $message),
            $statusCode,
        );
    }

    public static function fromDeserializationError(string $message, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to deserialize Limitless TCG API response: %s', $message),
            0,
            $previous,
        );
    }
}
