<?php

namespace App\ValueObject;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final readonly class TournamentId implements \Stringable
{
    private function __construct(
        public UuidV7 $value,
    ) {
    }

    public static function generate(): self
    {
        return new self(Uuid::v7());
    }

    public static function fromString(string $id): self
    {
        return new self(UuidV7::fromString($id));
    }

    public function __toString(): string
    {
        return $this->value->toRfc4122();
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }
}
