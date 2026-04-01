<?php

namespace App\Service;

use App\Entity\Player;
use App\Entity\PlayerTournamentResult;
use App\Entity\Tournament;
use App\Limitless\Dto\Tournament as TournamentDto;
use App\Limitless\Dto\TournamentStanding;
use App\Limitless\LimitlessTcgClient;
use App\Repository\PlayerRepository;
use App\Repository\PlayerTournamentResultRepository;
use App\Repository\TournamentRepository;
use App\ValueObject\LimitlessPlayerId;
use App\ValueObject\LimitlessTournamentId;
use App\ValueObject\PlayerId;
use App\ValueObject\PlayerTournamentResultId;
use App\ValueObject\TournamentId;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class PlayerStatsCollector
{
    public function __construct(
        private LimitlessTcgClient $client,
        private EntityManagerInterface $entityManager,
        private PlayerRepository $playerRepository,
        private TournamentRepository $tournamentRepository,
        private PlayerTournamentResultRepository $resultRepository,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Collect stats for players whose name contains the given filter.
     *
     * @return array{tournaments_scanned: int, players_found: int, results_persisted: int, results_updated: int}
     */
    public function collect(
        string $playerFilter = 'FrogEX',
        ?string $game = null,
        ?string $format = null,
        ?int $maxTournaments = null,
        ?\Closure $onTournamentProcessed = null,
    ): array {
        $stats = [
            'tournaments_scanned' => 0,
            'players_found' => 0,
            'results_persisted' => 0,
            'results_updated' => 0,
        ];

        $playersCache = [];
        $page = 1;

        do {
            $tournaments = $this->client->getTournaments(
                game: $game,
                format: $format,
                organizerId: null,
                limit: 50,
                page: $page,
            );

            foreach ($tournaments as $tournamentDto) {
                // Check if we've reached the maximum number of tournaments
                if (null !== $maxTournaments && $stats['tournaments_scanned'] >= $maxTournaments) {
                    return $stats;
                }

                $tournamentStats = $this->processTournament($tournamentDto, $playerFilter, $playersCache);

                ++$stats['tournaments_scanned'];
                $stats['players_found'] += $tournamentStats['players_found'];
                $stats['results_persisted'] += $tournamentStats['results_persisted'];
                $stats['results_updated'] += $tournamentStats['results_updated'];

                if (null !== $onTournamentProcessed) {
                    $onTournamentProcessed($tournamentDto, $tournamentStats);
                }
            }

            $page = $tournaments->getNextPage();
        } while ($tournaments->hasMore() && (null === $maxTournaments || $stats['tournaments_scanned'] < $maxTournaments));

        return $stats;
    }

    /**
     * @param array<string, Player> $playersCache
     *
     * @return array{players_found: int, results_persisted: int, results_updated: int}
     */
    private function processTournament(TournamentDto $tournamentDto, string $playerFilter, array &$playersCache): array
    {
        $stats = [
            'players_found' => 0,
            'results_persisted' => 0,
            'results_updated' => 0,
        ];

        try {
            $standings = $this->client->getTournamentStandings($tournamentDto->id);
        } catch (\Exception $e) {
            $this->logger?->warning('Failed to fetch standings for tournament {id}: {error}', [
                'id' => $tournamentDto->id,
                'error' => $e->getMessage(),
            ]);

            return $stats;
        }

        // Filter standings for players matching the filter (case-insensitive)
        $matchingStandings = array_filter(
            $standings,
            fn (TournamentStanding $standing) => false !== stripos($standing->name, $playerFilter)
        );

        if (0 === \count($matchingStandings)) {
            return $stats;
        }

        // Wrap database operations in a transaction
        $this->entityManager->wrapInTransaction(function () use ($tournamentDto, $matchingStandings, &$playersCache, &$stats): void {
            // Get or create tournament entity (only if we have matching players)
            $tournament = $this->getOrCreateTournament($tournamentDto);

            foreach ($matchingStandings as $standing) {
                ++$stats['players_found'];

                $player = $this->getOrCreatePlayer($standing, $playersCache);
                $resultStats = $this->upsertResult($player, $tournament, $standing);

                $stats['results_persisted'] += $resultStats['created'];
                $stats['results_updated'] += $resultStats['updated'];
            }
        });

        return $stats;
    }

    private function getOrCreateTournament(TournamentDto $dto): Tournament
    {
        $externalId = LimitlessTournamentId::fromString($dto->id);
        $tournament = $this->tournamentRepository->findByExternalId($externalId);

        if (null !== $tournament) {
            // Update tournament data in case it changed
            $tournament->setName($dto->name)
                ->setGame($dto->game)
                ->setFormat($dto->format)
                ->setDate($dto->date)
                ->setPlayerCount($dto->players);

            return $tournament;
        }

        $tournament = new Tournament(
            id: TournamentId::generate(),
            externalId: $externalId,
            name: $dto->name,
            game: $dto->game,
            date: $dto->date,
        );
        $tournament->setFormat($dto->format)
            ->setPlayerCount($dto->players);

        $this->entityManager->persist($tournament);

        return $tournament;
    }

    /**
     * @param array<string, Player> $cache
     */
    private function getOrCreatePlayer(TournamentStanding $standing, array &$cache): Player
    {
        // Check cache first
        if (isset($cache[$standing->player])) {
            $player = $cache[$standing->player];

            // Update player data in case it changed
            $player->setName($standing->name)
                ->setCountry($standing->country);

            return $player;
        }

        // Check database
        $externalId = LimitlessPlayerId::fromString($standing->player);
        $player = $this->playerRepository->findByExternalId($externalId);

        if (null !== $player) {
            // Update player data in case it changed
            $player->setName($standing->name)
                ->setCountry($standing->country);

            $cache[$standing->player] = $player;

            return $player;
        }

        // Create new player
        $player = new Player(
            id: PlayerId::generate(),
            externalId: $externalId,
            name: $standing->name,
        );
        $player->setCountry($standing->country);

        $this->entityManager->persist($player);
        $cache[$standing->player] = $player;

        return $player;
    }

    /**
     * @return array{created: int, updated: int}
     */
    private function upsertResult(Player $player, Tournament $tournament, TournamentStanding $standing): array
    {
        $result = $this->resultRepository->findByPlayerAndTournament($player, $tournament);

        if (null !== $result) {
            // Update existing result
            $this->updateResult($result, $standing);

            return ['created' => 0, 'updated' => 1];
        }

        // Create new result
        $result = new PlayerTournamentResult(
            id: PlayerTournamentResultId::generate(),
            player: $player,
            tournament: $tournament,
        );

        $this->updateResult($result, $standing);
        $this->entityManager->persist($result);

        return ['created' => 1, 'updated' => 0];
    }

    private function updateResult(PlayerTournamentResult $result, TournamentStanding $standing): void
    {
        $result->setPlacing($standing->placing)
            ->setWins($standing->record->wins)
            ->setLosses($standing->record->losses)
            ->setTies($standing->record->ties)
            ->setDeckName($standing->deck?->name)
            ->setDeckId($standing->deck?->id)
            ->setDropped($standing->hasDropped());
    }
}
