<?php

namespace App\Tests\Unit\Limitless;

use App\Limitless\Dto\PaginatedTournaments;
use App\Limitless\Dto\Tournament;
use App\Limitless\Dto\TournamentStanding;
use App\Limitless\Exception\LimitlessTcgException;
use App\Limitless\LimitlessTcgClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LimitlessTcgClientTest extends TestCase
{
    #[Test]
    public function getTournamentsReturnsAPaginatedListOfTournaments(): void
    {
        $responseData = [
            [
                'id' => '63fcb6d32fb42a11441fb777',
                'game' => 'VGC',
                'format' => '23S2',
                'name' => 'Torneio Semanal VGC Brasil #07',
                'date' => '2023-03-02T23:00:00.000Z',
                'players' => 28,
            ],
            [
                'id' => '63e931d12fb42a11441f513e',
                'game' => 'PTCG',
                'format' => 'STANDARD',
                'name' => 'Trust Your Feet PTCGLive #15 (SWSH to CRZ)',
                'date' => '2023-03-02T22:00:00.000Z',
                'players' => 47,
            ],
        ];

        $mockResponse = new MockResponse(json_encode($responseData), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse, 'https://play.limitlesstcg.com/api/');
        $cache = new ArrayAdapter();

        $client = new LimitlessTcgClient($httpClient, $cache);
        $result = $client->getTournaments();

        self::assertInstanceOf(PaginatedTournaments::class, $result);
        self::assertCount(2, $result);
        self::assertSame(1, $result->page);
        self::assertSame(50, $result->limit);
        self::assertFalse($result->hasMore());

        $tournament = $result->items[0];
        self::assertInstanceOf(Tournament::class, $tournament);
        self::assertSame('63fcb6d32fb42a11441fb777', $tournament->id);
        self::assertSame('VGC', $tournament->game);
        self::assertSame('23S2', $tournament->format);
        self::assertSame('Torneio Semanal VGC Brasil #07', $tournament->name);
        self::assertSame(28, $tournament->players);
    }

    #[Test]
    public function getTournamentsWithFilters(): void
    {
        $responseData = [
            [
                'id' => '63e931d12fb42a11441f513e',
                'game' => 'PTCG',
                'format' => 'STANDARD',
                'name' => 'Trust Your Feet PTCGLive #15',
                'date' => '2023-03-02T22:00:00.000Z',
                'players' => 47,
            ],
        ];

        $mockResponse = new MockResponse(json_encode($responseData), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);

        $httpClient = new MockHttpClient(function ($method, $url) use ($mockResponse): MockResponse {
            self::assertSame('GET', $method);
            self::assertStringContainsString('game=PTCG', $url);
            self::assertStringContainsString('format=STANDARD', $url);
            self::assertStringContainsString('limit=10', $url);
            self::assertStringContainsString('page=2', $url);

            return $mockResponse;
        }, 'https://play.limitlesstcg.com/api/');

        $cache = new ArrayAdapter();

        $client = new LimitlessTcgClient($httpClient, $cache);
        $result = $client->getTournaments(
            game: 'PTCG',
            format: 'STANDARD',
            limit: 10,
            page: 2,
        );

        self::assertCount(1, $result);
        self::assertSame(2, $result->page);
        self::assertSame(10, $result->limit);
    }

    #[Test]
    public function getTournamentsHasMoreReturnsTrueWhenPageIsFull(): void
    {
        $responseData = array_fill(0, 50, [
            'id' => 'test-id',
            'game' => 'PTCG',
            'format' => 'STANDARD',
            'name' => 'Test Tournament',
            'date' => '2023-03-02T22:00:00.000Z',
            'players' => 10,
        ]);

        $mockResponse = new MockResponse(json_encode($responseData), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse, 'https://play.limitlesstcg.com/api/');
        $cache = new ArrayAdapter();

        $client = new LimitlessTcgClient($httpClient, $cache);
        $result = $client->getTournaments();

        self::assertTrue($result->hasMore());
        self::assertSame(2, $result->getNextPage());
    }

    #[Test]
    public function getTournamentStandingsReturnsListOfStandings(): void
    {
        $responseData = [
            [
                'player' => 'espel',
                'name' => 'Tsubasa Shimizu',
                'country' => 'JP',
                'placing' => 1,
                'record' => [
                    'wins' => 13,
                    'losses' => 2,
                    'ties' => 0,
                ],
                'decklist' => ['card1' => 4, 'card2' => 3],
                'deck' => [
                    'id' => 'lost-zone-box',
                    'name' => 'Lost Zone Box',
                    'icons' => ['comfey', 'sableye'],
                ],
                'drop' => null,
            ],
            [
                'player' => 'player2',
                'name' => 'Player Two',
                'country' => 'US',
                'placing' => 2,
                'record' => [
                    'wins' => 12,
                    'losses' => 3,
                    'ties' => 0,
                ],
                'drop' => 5,
            ],
        ];

        $mockResponse = new MockResponse(json_encode($responseData), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse, 'https://play.limitlesstcg.com/api/');
        $cache = new ArrayAdapter();

        $client = new LimitlessTcgClient($httpClient, $cache);
        $result = $client->getTournamentStandings('63fcb6d32fb42a11441fb777');

        self::assertCount(2, $result);

        $standing1 = $result[0];
        self::assertInstanceOf(TournamentStanding::class, $standing1);
        self::assertSame('espel', $standing1->player);
        self::assertSame('Tsubasa Shimizu', $standing1->name);
        self::assertSame('JP', $standing1->country);
        self::assertSame(1, $standing1->placing);
        self::assertSame(13, $standing1->record->wins);
        self::assertSame(2, $standing1->record->losses);
        self::assertSame(0, $standing1->record->ties);
        self::assertNotNull($standing1->decklist);
        self::assertNotNull($standing1->deck);
        self::assertSame('lost-zone-box', $standing1->deck->id);
        self::assertSame('Lost Zone Box', $standing1->deck->name);
        self::assertSame(['comfey', 'sableye'], $standing1->deck->icons);
        self::assertFalse($standing1->hasDropped());

        $standing2 = $result[1];
        self::assertNull($standing2->decklist);
        self::assertNull($standing2->deck);
        self::assertTrue($standing2->hasDropped());
        self::assertSame(5, $standing2->drop);
    }

    #[Test]
    public function getTournamentsThrowsExceptionOnHttpError(): void
    {
        $mockResponse = new MockResponse('Not Found', [
            'http_code' => 404,
        ]);

        $httpClient = new MockHttpClient($mockResponse, 'https://play.limitlesstcg.com/api/');
        $cache = new ArrayAdapter();

        $client = new LimitlessTcgClient($httpClient, $cache);

        $this->expectException(LimitlessTcgException::class);
        $this->expectExceptionMessage('Limitless TCG API error (HTTP 404)');

        $client->getTournaments();
    }

    #[Test]
    public function getTournamentStandingsThrowsExceptionOnHttpError(): void
    {
        $mockResponse = new MockResponse('Server Error', [
            'http_code' => 500,
        ]);

        $httpClient = new MockHttpClient($mockResponse, 'https://play.limitlesstcg.com/api/');
        $cache = new ArrayAdapter();

        $client = new LimitlessTcgClient($httpClient, $cache);

        $this->expectException(LimitlessTcgException::class);
        $this->expectExceptionMessage('Limitless TCG API error (HTTP 500)');

        $client->getTournamentStandings('invalid-id');
    }

    #[Test]
    public function getTournamentsUsesCachedResponse(): void
    {
        $callCount = 0;
        $responseData = [
            [
                'id' => 'test-id',
                'game' => 'PTCG',
                'format' => 'STANDARD',
                'name' => 'Test Tournament',
                'date' => '2023-03-02T22:00:00.000Z',
                'players' => 10,
            ],
        ];

        $httpClient = new MockHttpClient(function () use (&$callCount, $responseData): MockResponse {
            ++$callCount;

            return new MockResponse(json_encode($responseData), [
                'http_code' => 200,
                'response_headers' => ['Content-Type: application/json'],
            ]);
        }, 'https://play.limitlesstcg.com/api/');

        $cache = new ArrayAdapter();

        $client = new LimitlessTcgClient($httpClient, $cache);

        // First call should hit the API
        $client->getTournaments(game: 'PTCG');
        self::assertSame(1, $callCount);

        // Second call with same parameters should use cache
        $client->getTournaments(game: 'PTCG');
        self::assertSame(1, $callCount);

        // Third call with different parameters should hit the API
        $client->getTournaments(game: 'VGC');
        self::assertSame(2, $callCount);
    }

    #[Test]
    public function tournamentRecordCalculatesWinRateCorrectly(): void
    {
        $responseData = [
            [
                'player' => 'test',
                'name' => 'Test Player',
                'country' => 'US',
                'placing' => 1,
                'record' => [
                    'wins' => 8,
                    'losses' => 2,
                    'ties' => 0,
                ],
            ],
        ];

        $mockResponse = new MockResponse(json_encode($responseData), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse, 'https://play.limitlesstcg.com/api/');
        $cache = new ArrayAdapter();

        $client = new LimitlessTcgClient($httpClient, $cache);
        $standings = $client->getTournamentStandings('test-id');

        $record = $standings[0]->record;
        self::assertSame(10, $record->getTotal());
        self::assertSame(0.8, $record->getWinRate());
    }

    #[Test]
    public function paginatedTournamentsIsIterableAndCountable(): void
    {
        $responseData = [
            [
                'id' => 'id1',
                'game' => 'PTCG',
                'format' => 'STANDARD',
                'name' => 'Tournament 1',
                'date' => '2023-03-01T22:00:00.000Z',
                'players' => 10,
            ],
            [
                'id' => 'id2',
                'game' => 'PTCG',
                'format' => 'STANDARD',
                'name' => 'Tournament 2',
                'date' => '2023-03-02T22:00:00.000Z',
                'players' => 20,
            ],
        ];

        $mockResponse = new MockResponse(json_encode($responseData), [
            'http_code' => 200,
            'response_headers' => ['Content-Type: application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse, 'https://play.limitlesstcg.com/api/');
        $cache = new ArrayAdapter();

        $client = new LimitlessTcgClient($httpClient, $cache);
        $result = $client->getTournaments();

        // Test Countable
        self::assertCount(2, $result);

        // Test IteratorAggregate
        $names = [];
        foreach ($result as $tournament) {
            $names[] = $tournament->name;
        }
        self::assertSame(['Tournament 1', 'Tournament 2'], $names);

        // Test isEmpty
        self::assertFalse($result->isEmpty());
    }
}
