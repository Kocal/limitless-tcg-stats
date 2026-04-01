<?php

namespace App\Limitless\Dto;

final readonly class TournamentOrganizer
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $logo = null,
    ) {
    }

    /**
     * @param array{id: int, name: string, logo?: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            logo: $data['logo'] ?? null,
        );
    }
}
