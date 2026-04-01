<?php

namespace App\Scheduler\Message;

/**
 * Message dispatched by the scheduler to trigger player stats collection.
 */
final readonly class CollectPlayerStatsMessage
{
    public function __construct(
        public string $playerFilter = 'FrogEX',
        public ?string $game = null,
        public ?string $format = null,
        public ?int $maxTournaments = null,
    ) {
    }
}
