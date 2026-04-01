<?php

namespace App\Tests\Unit\Limitless\Dto;

use App\Limitless\Dto\TournamentDeck;
use App\Limitless\Dto\TournamentRecord;
use App\Limitless\Dto\TournamentStanding;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TournamentStandingTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesStandingWithAllFields(): void
    {
        $standing = TournamentStanding::fromArray([
            'player' => 'espel',
            'name' => 'Tsubasa Shimizu',
            'country' => 'JP',
            'placing' => 1,
            'record' => [
                'wins' => 13,
                'losses' => 2,
                'ties' => 0,
            ],
            'decklist' => ['card1' => 4],
            'deck' => [
                'id' => 'lost-zone-box',
                'name' => 'Lost Zone Box',
                'icons' => ['comfey', 'sableye'],
            ],
            'drop' => null,
        ]);

        self::assertSame('espel', $standing->player);
        self::assertSame('Tsubasa Shimizu', $standing->name);
        self::assertSame('JP', $standing->country);
        self::assertSame(1, $standing->placing);
        self::assertInstanceOf(TournamentRecord::class, $standing->record);
        self::assertSame(13, $standing->record->wins);
        self::assertSame(['card1' => 4], $standing->decklist);
        self::assertInstanceOf(TournamentDeck::class, $standing->deck);
        self::assertSame('lost-zone-box', $standing->deck->id);
        self::assertNull($standing->drop);
        self::assertFalse($standing->hasDropped());
    }

    #[Test]
    public function fromArrayCreatesStandingWithMinimalFields(): void
    {
        $standing = TournamentStanding::fromArray([
            'player' => 'player1',
            'name' => 'Player One',
            'country' => 'US',
            'placing' => 10,
            'record' => [
                'wins' => 5,
                'losses' => 5,
                'ties' => 0,
            ],
        ]);

        self::assertSame('player1', $standing->player);
        self::assertNull($standing->decklist);
        self::assertNull($standing->deck);
        self::assertNull($standing->drop);
    }

    #[Test]
    public function hasDroppedReturnsTrueWhenPlayerDropped(): void
    {
        $standing = TournamentStanding::fromArray([
            'player' => 'dropped_player',
            'name' => 'Dropped Player',
            'country' => 'FR',
            'placing' => 50,
            'record' => [
                'wins' => 2,
                'losses' => 3,
                'ties' => 0,
            ],
            'drop' => 5,
        ]);

        self::assertTrue($standing->hasDropped());
        self::assertSame(5, $standing->drop);
    }
}
