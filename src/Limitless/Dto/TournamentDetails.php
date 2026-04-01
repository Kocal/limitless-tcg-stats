<?php

namespace App\Limitless\Dto;

final readonly class TournamentDetails
{
    /**
     * @param list<TournamentPhase> $phases
     */
    public function __construct(
        public string $id,
        public string $game,
        public ?string $format,
        public string $name,
        public \DateTimeImmutable $date,
        public int $players,
        public TournamentOrganizer $organizer,
        public ?string $platform,
        public bool $decklists,
        public bool $isPublic,
        public bool $isOnline,
        public array $phases,
    ) {
    }

    /**
     * @param array{
     *     id: string,
     *     game: string,
     *     format?: string|null,
     *     name: string,
     *     date: string,
     *     players: int,
     *     organizer: array{id: int, name: string, logo?: string|null},
     *     platform?: string|null,
     *     decklists: bool,
     *     isPublic: bool,
     *     isOnline: bool,
     *     phases: list<array{phase: int, type: string, rounds: int, mode: string}>
     * } $data
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
            organizer: TournamentOrganizer::fromArray($data['organizer']),
            platform: $data['platform'] ?? null,
            decklists: $data['decklists'],
            isPublic: $data['isPublic'],
            isOnline: $data['isOnline'],
            phases: array_map(
                fn (array $phase): TournamentPhase => TournamentPhase::fromArray($phase),
                $data['phases'] ?? [],
            ),
        );
    }

    /**
     * Returns the phases as an array suitable for JSON storage.
     *
     * @return list<array{phase: int, type: string, rounds: int, mode: string}>
     */
    public function getPhasesAsArray(): array
    {
        return array_map(
            fn (TournamentPhase $phase): array => $phase->toArray(),
            $this->phases,
        );
    }
}
