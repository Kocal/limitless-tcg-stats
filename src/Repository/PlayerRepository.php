<?php

namespace App\Repository;

use App\Entity\Player;
use App\ValueObject\LimitlessPlayerId;
use App\ValueObject\PlayerId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function findById(PlayerId $id): ?Player
    {
        return $this->find($id);
    }

    public function findByExternalId(LimitlessPlayerId $externalId): ?Player
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * Find all players with at least a minimum number of tournaments.
     *
     * @return Player[]
     */
    public function findAllWithMinimumTournaments(int $minimumTournaments = 3): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('App\Entity\PlayerTournamentResult', 'r', 'WITH', 'r.player = p')
            ->groupBy('p.id')
            ->having('COUNT(r.id) >= :minimumTournaments')
            ->setParameter('minimumTournaments', $minimumTournaments)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find players whose name contains the given search string (case-insensitive).
     *
     * @return Player[]
     */
    public function findByNameContaining(string $search): array
    {
        return $this->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE LOWER(:search)')
            ->setParameter('search', '%'.$search.'%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Player $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
