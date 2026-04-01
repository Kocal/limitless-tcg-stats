<?php

namespace App\Scheduler\Handler;

use App\Scheduler\Message\CollectPlayerStatsMessage;
use App\Service\PlayerStatsCollector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CollectPlayerStatsHandler
{
    public function __construct(
        private PlayerStatsCollector $collector,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(CollectPlayerStatsMessage $message): void
    {
        $this->logger?->info('Starting scheduled player stats collection', [
            'playerFilter' => $message->playerFilter,
            'game' => $message->game,
            'format' => $message->format,
            'maxTournaments' => $message->maxTournaments,
        ]);

        $stats = $this->collector->collect(
            playerFilter: $message->playerFilter,
            game: $message->game,
            format: $message->format,
            maxTournaments: $message->maxTournaments,
        );

        $this->logger?->info('Completed scheduled player stats collection', [
            'tournaments_scanned' => $stats['tournaments_scanned'],
            'players_found' => $stats['players_found'],
            'results_persisted' => $stats['results_persisted'],
            'results_updated' => $stats['results_updated'],
        ]);
    }
}
