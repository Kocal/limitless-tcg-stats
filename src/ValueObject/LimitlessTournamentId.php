<?php

namespace App\ValueObject;

final readonly class LimitlessTournamentId implements \Stringable
{
    private function __construct(
        public string $value,
    ) {
        if ('' === $value) {
            throw new \InvalidArgumentException('LimitlessTournamentId cannot be empty');
        }
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
