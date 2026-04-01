<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\ValueObject\LimitlessTournamentId;
use App\ValueObject\TournamentId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournament>
 */
class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function findById(TournamentId $id): ?Tournament
    {
        return $this->find($id);
    }

    public function findByExternalId(LimitlessTournamentId $externalId): ?Tournament
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * Find tournaments by game and/or format.
     *
     * @return Tournament[]
     */
    public function findByGameAndFormat(?string $game = null, ?string $format = null): array
    {
        $qb = $this->createQueryBuilder('t');

        if (null !== $game) {
            $qb->andWhere('t.game = :game')
                ->setParameter('game', $game);
        }

        if (null !== $format) {
            $qb->andWhere('t.format = :format')
                ->setParameter('format', $format);
        }

        return $qb->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Tournament $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
