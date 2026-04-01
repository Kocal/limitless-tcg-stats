<?php

namespace App\Limitless\Dto;

final readonly class TournamentRecord
{
    public function __construct(
        public int $wins,
        public int $losses,
        public int $ties,
    ) {
    }

    /**
     * @param array{wins: int, losses: int, ties: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            wins: $data['wins'],
            losses: $data['losses'],
            ties: $data['ties'],
        );
    }

    public function getTotal(): int
    {
        return $this->wins + $this->losses + $this->ties;
    }

    public function getWinRate(): float
    {
        $total = $this->getTotal();

        if (0 === $total) {
            return 0.0;
        }

        return $this->wins / $total;
    }
}
