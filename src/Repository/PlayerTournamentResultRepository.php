<?php

namespace App\Repository;

use App\Entity\Player;
use App\Entity\PlayerTournamentResult;
use App\Entity\Tournament;
use App\ValueObject\PlayerTournamentResultId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerTournamentResult>
 */
class PlayerTournamentResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerTournamentResult::class);
    }

    public function findById(PlayerTournamentResultId $id): ?PlayerTournamentResult
    {
        return $this->find($id);
    }

    /**
     * Find all results for a player, ordered by tournament date (most recent first).
     *
     * @return PlayerTournamentResult[]
     */
    public function findByPlayer(Player $player): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.tournament', 't')
            ->where('r.player = :player')
            ->setParameter('player', $player)
            ->orderBy('t.date', 'DESC');

        self::applyBaseFilters($qb, 't');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find a result by player and tournament (unique constraint).
     */
    public function findByPlayerAndTournament(Player $player, Tournament $tournament): ?PlayerTournamentResult
    {
        return $this->findOneBy([
            'player' => $player,
            'tournament' => $tournament,
        ]);
    }

    /**
     * Get statistics summary for a player.
     *
     * @return array{tournaments: int, total_wins: int, total_losses: int, total_ties: int, best_placing: int|null, best_placing_players: int|null, avg_placing: float|null}
     */
    public function getPlayerStats(Player $player): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select([
                'COUNT(r.id) as tournaments',
                'SUM(r.wins) as total_wins',
                'SUM(r.losses) as total_losses',
                'SUM(r.ties) as total_ties',
                'MIN(r.finalPlacing) as best_placing',
                'AVG(r.finalPlacing) as avg_placing',
            ])
            ->where('r.player = :player')
            ->setParameter('player', $player);

        $result = $qb->getQuery()->getSingleResult();

        // Get the number of players in the tournament with the best placing
        $bestPlacingPlayers = null;
        if ($result['best_placing']) {
            $bestResult = $this->createQueryBuilder('r')
                ->select('t.playerCount')
                ->join('r.tournament', 't')
                ->where('r.player = :player')
                ->andWhere('r.finalPlacing = :bestPlacing')
                ->setParameter('player', $player)
                ->setParameter('bestPlacing', $result['best_placing'])
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();

            $bestPlacingPlayers = $bestResult ? (int) $bestResult : null;
        }

        return [
            'tournaments' => (int) $result['tournaments'],
            'total_wins' => (int) ($result['total_wins'] ?? 0),
            'total_losses' => (int) ($result['total_losses'] ?? 0),
            'total_ties' => (int) ($result['total_ties'] ?? 0),
            'best_placing' => $result['best_placing'] ? (int) $result['best_placing'] : null,
            'best_placing_players' => $bestPlacingPlayers,
            'avg_placing' => $result['avg_placing'] ? (float) $result['avg_placing'] : null,
        ];
    }

    public function save(PlayerTournamentResult $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find most recent results across all players.
     *
     * @return PlayerTournamentResult[]
     */
    public function findRecentResults(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.tournament', 't')
            ->orderBy('t.date', 'DESC')
            ->setMaxResults($limit);

        self::applyBaseFilters($qb, 't');

        return $qb->getQuery()->getResult();
    }

    private static function applyBaseFilters(QueryBuilder $qb, string $tournamentAlias): void
    {
        $qb
            ->andWhere($qb->expr()->gte($tournamentAlias . '.playerCount', ':minPlayers'))
            ->setParameter('minPlayers', 64)
            ->andWhere($qb->expr()->orX()
                ->add($qb->expr()->isNull($tournamentAlias . '.format'))
                ->add($qb->expr()->notIn($tournamentAlias . '.format', ':excludedFormats'))
            )
            ->setParameter('excludedFormats', ['CUSTOM', 'NO EX'])
        ;
    }
}
