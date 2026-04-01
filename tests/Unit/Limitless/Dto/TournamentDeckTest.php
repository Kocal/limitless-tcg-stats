<?php

namespace App\Tests\Unit\Limitless\Dto;

use App\Limitless\Dto\TournamentDeck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TournamentDeckTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesDeck(): void
    {
        $deck = TournamentDeck::fromArray([
            'id' => 'lost-zone-box',
            'name' => 'Lost Zone Box',
            'icons' => ['comfey', 'sableye'],
        ]);

        self::assertSame('lost-zone-box', $deck->id);
        self::assertSame('Lost Zone Box', $deck->name);
        self::assertSame(['comfey', 'sableye'], $deck->icons);
    }

    #[Test]
    public function fromArrayWithEmptyIcons(): void
    {
        $deck = TournamentDeck::fromArray([
            'id' => 'unknown',
            'name' => 'Unknown Deck',
            'icons' => [],
        ]);

        self::assertSame([], $deck->icons);
    }
}
