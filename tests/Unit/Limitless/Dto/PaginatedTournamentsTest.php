<?php

namespace App\Tests\Unit\Limitless\Dto;

use App\Limitless\Dto\PaginatedTournaments;
use App\Limitless\Dto\Tournament;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaginatedTournamentsTest extends TestCase
{
    #[Test]
    public function hasMoreReturnsTrueWhenItemsEqualLimit(): void
    {
        $tournaments = array_map(
            fn (int $i): Tournament => new Tournament(
                id: "id-$i",
                game: 'PTCG',
                format: 'STANDARD',
                name: "Tournament $i",
                date: new \DateTimeImmutable(),
                players: 10,
            ),
            range(1, 50),
        );

        $paginated = new PaginatedTournaments($tournaments, page: 1, limit: 50);

        self::assertTrue($paginated->hasMore());
    }

    #[Test]
    public function hasMoreReturnsFalseWhenItemsLessThanLimit(): void
    {
        $tournaments = [
            new Tournament(
                id: 'id-1',
                game: 'PTCG',
                format: 'STANDARD',
                name: 'Tournament 1',
                date: new \DateTimeImmutable(),
                players: 10,
            ),
        ];

        $paginated = new PaginatedTournaments($tournaments, page: 1, limit: 50);

        self::assertFalse($paginated->hasMore());
    }

    #[Test]
    public function getNextPageReturnsIncrementedPage(): void
    {
        $paginated = new PaginatedTournaments([], page: 3, limit: 50);

        self::assertSame(4, $paginated->getNextPage());
    }

    #[Test]
    public function isEmptyReturnsTrueForEmptyList(): void
    {
        $paginated = new PaginatedTournaments([], page: 1, limit: 50);

        self::assertTrue($paginated->isEmpty());
    }

    #[Test]
    public function isEmptyReturnsFalseForNonEmptyList(): void
    {
        $tournaments = [
            new Tournament(
                id: 'id-1',
                game: 'PTCG',
                format: 'STANDARD',
                name: 'Tournament 1',
                date: new \DateTimeImmutable(),
                players: 10,
            ),
        ];

        $paginated = new PaginatedTournaments($tournaments, page: 1, limit: 50);

        self::assertFalse($paginated->isEmpty());
    }

    #[Test]
    public function countReturnsNumberOfItems(): void
    {
        $tournaments = [
            new Tournament('id-1', 'PTCG', 'STANDARD', 'T1', new \DateTimeImmutable(), 10),
            new Tournament('id-2', 'PTCG', 'STANDARD', 'T2', new \DateTimeImmutable(), 20),
            new Tournament('id-3', 'PTCG', 'STANDARD', 'T3', new \DateTimeImmutable(), 30),
        ];

        $paginated = new PaginatedTournaments($tournaments, page: 1, limit: 50);

        self::assertCount(3, $paginated);
    }

    #[Test]
    public function canBeIteratedOver(): void
    {
        $tournaments = [
            new Tournament('id-1', 'PTCG', 'STANDARD', 'Tournament 1', new \DateTimeImmutable(), 10),
            new Tournament('id-2', 'PTCG', 'STANDARD', 'Tournament 2', new \DateTimeImmutable(), 20),
        ];

        $paginated = new PaginatedTournaments($tournaments, page: 1, limit: 50);

        $names = [];
        foreach ($paginated as $tournament) {
            $names[] = $tournament->name;
        }

        self::assertSame(['Tournament 1', 'Tournament 2'], $names);
    }
}
