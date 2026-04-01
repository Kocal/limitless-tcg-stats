<?php

namespace App\Limitless\Dto;

final readonly class TournamentDeck
{
    /**
     * @param list<string> $icons
     */
    public function __construct(
        public ?string $id,
        public ?string $name,
        public array $icons,
    ) {
    }

    /**
     * @param array{id?: string|null, name?: string|null, icons?: list<string>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? null,
            icons: $data['icons'] ?? [],
        );
    }
}
