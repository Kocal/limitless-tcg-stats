<?php

namespace App\Limitless\Dto;

final readonly class TournamentStanding
{
    /**
     * @param array<string, mixed>|null $decklist
     */
    public function __construct(
        public string $player,
        public string $name,
        public ?string $country,
        public ?int $placing,
        public TournamentRecord $record,
        public ?array $decklist,
        public ?TournamentDeck $deck,
        public ?int $drop,
    ) {
    }

    /**
     * @param array{
     *     player: string,
     *     name: string,
     *     country?: string|null,
     *     placing?: int|null,
     *     record: array{wins: int, losses: int, ties: int},
     *     decklist?: array<string, mixed>|null,
     *     deck?: array{id: string, name: string, icons: list<string>}|null,
     *     drop?: int|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            player: $data['player'],
            name: $data['name'],
            country: $data['country'] ?? null,
            placing: $data['placing'] ?? null,
            record: TournamentRecord::fromArray($data['record']),
            decklist: $data['decklist'] ?? null,
            deck: isset($data['deck']) ? TournamentDeck::fromArray($data['deck']) : null,
            drop: $data['drop'] ?? null,
        );
    }

    public function hasDropped(): bool
    {
        return null !== $this->drop;
    }
}
