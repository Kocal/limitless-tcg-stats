<?php

namespace App\Tests\Unit\Limitless\Dto;

use App\Limitless\Dto\TournamentRecord;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TournamentRecordTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesRecord(): void
    {
        $record = TournamentRecord::fromArray([
            'wins' => 10,
            'losses' => 3,
            'ties' => 2,
        ]);

        self::assertSame(10, $record->wins);
        self::assertSame(3, $record->losses);
        self::assertSame(2, $record->ties);
    }

    #[Test]
    public function getTotalReturnsSum(): void
    {
        $record = new TournamentRecord(wins: 10, losses: 3, ties: 2);

        self::assertSame(15, $record->getTotal());
    }

    #[Test]
    public function getWinRateCalculatesCorrectly(): void
    {
        $record = new TournamentRecord(wins: 8, losses: 2, ties: 0);

        self::assertSame(0.8, $record->getWinRate());
    }

    #[Test]
    public function getWinRateReturnsZeroWhenNoGames(): void
    {
        $record = new TournamentRecord(wins: 0, losses: 0, ties: 0);

        self::assertSame(0.0, $record->getWinRate());
    }

    #[Test]
    public function getWinRateWithTies(): void
    {
        $record = new TournamentRecord(wins: 5, losses: 3, ties: 2);

        // 5 wins out of 10 games = 50%
        self::assertSame(0.5, $record->getWinRate());
    }
}
