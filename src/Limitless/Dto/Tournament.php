<?php

namespace App\Limitless\Dto;

final readonly class Tournament
{
    public function __construct(
        public string $id,
        public string $game,
        public ?string $format,
        public string $name,
        public \DateTimeImmutable $date,
        public int $players,
    ) {
    }

    /**
     * @param array{id: string, game: string, format?: string|null, name: string, date: string, players: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            game: $data['game'],
            format: $data['format'] ?? null,
            name: $data['name'],
            date: new \DateTimeImmutable($data['date']),
            players: $data['players'],
        );
    }
}
