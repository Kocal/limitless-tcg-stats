<?php

namespace App\Limitless\Dto;

final readonly class TournamentPhase
{
    public function __construct(
        public int $phase,
        public string $type,
        public int $rounds,
        public string $mode,
    ) {
    }

    /**
     * @param array{phase: int, type: string, rounds: int, mode: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            phase: $data['phase'],
            type: $data['type'],
            rounds: $data['rounds'],
            mode: $data['mode'],
        );
    }

    /**
     * @return array{phase: int, type: string, rounds: int, mode: string}
     */
    public function toArray(): array
    {
        return [
            'phase' => $this->phase,
            'type' => $this->type,
            'rounds' => $this->rounds,
            'mode' => $this->mode,
        ];
    }
}
