<?php

namespace App\Tests\Unit\Limitless\Dto;

use App\Limitless\Dto\Tournament;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TournamentTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesTournament(): void
    {
        $tournament = Tournament::fromArray([
            'id' => '63fcb6d32fb42a11441fb777',
            'game' => 'VGC',
            'format' => '23S2',
            'name' => 'Torneio Semanal VGC Brasil #07',
            'date' => '2023-03-02T23:00:00.000Z',
            'players' => 28,
        ]);

        self::assertSame('63fcb6d32fb42a11441fb777', $tournament->id);
        self::assertSame('VGC', $tournament->game);
        self::assertSame('23S2', $tournament->format);
        self::assertSame('Torneio Semanal VGC Brasil #07', $tournament->name);
        self::assertSame(28, $tournament->players);
        self::assertInstanceOf(\DateTimeImmutable::class, $tournament->date);
        self::assertSame('2023-03-02', $tournament->date->format('Y-m-d'));
    }

    #[Test]
    public function fromArrayParsesDateCorrectly(): void
    {
        $tournament = Tournament::fromArray([
            'id' => 'test',
            'game' => 'PTCG',
            'format' => 'STANDARD',
            'name' => 'Test',
            'date' => '2024-12-25T15:30:00.000Z',
            'players' => 100,
        ]);

        self::assertSame('2024', $tournament->date->format('Y'));
        self::assertSame('12', $tournament->date->format('m'));
        self::assertSame('25', $tournament->date->format('d'));
        self::assertSame('15', $tournament->date->format('H'));
        self::assertSame('30', $tournament->date->format('i'));
    }
}
