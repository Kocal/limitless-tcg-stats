<?php

namespace App\Repository;

use App\Entity\Player;
use App\Entity\PlayerTournamentResult;
use App\Entity\Tournament;
use App\ValueObject\PlayerTournamentResultId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        return $this->createQueryBuilder('r')
            ->join('r.tournament', 't')
            ->where('r.player = :player')
            ->setParameter('player', $player)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all results for a tournament, ordered by placing.
     *
     * @return PlayerTournamentResult[]
     */
    public function findByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('r.finalPlacing', 'ASC')
            ->getQuery()
            ->getResult();
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
     * @return array{tournaments: int, total_wins: int, total_losses: int, total_ties: int, best_placing: int|null, avg_placing: float|null}
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

        return [
            'tournaments' => (int) $result['tournaments'],
            'total_wins' => (int) ($result['total_wins'] ?? 0),
            'total_losses' => (int) ($result['total_losses'] ?? 0),
            'total_ties' => (int) ($result['total_ties'] ?? 0),
            'best_placing' => $result['best_placing'] ? (int) $result['best_placing'] : null,
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
}
