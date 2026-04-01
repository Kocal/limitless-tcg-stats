<?php

namespace App\Limitless;

use App\Limitless\Dto\PaginatedTournaments;
use App\Limitless\Dto\Tournament;
use App\Limitless\Dto\TournamentDetails;
use App\Limitless\Dto\TournamentStanding;
use App\Limitless\Exception\LimitlessTcgException;
use App\Limitless\Exception\RateLimitException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class LimitlessTcgClient
{
    private const int CACHE_TTL_TOURNAMENTS = 60 * 60 * 24;
    private const int CACHE_TTL_DETAILS = 60 * 60 * 24 * 30;
    private const int CACHE_TTL_STANDINGS = 60 * 60 * 24 * 30;

    public function __construct(
        private HttpClientInterface $limitlessClient,
        private CacheInterface $cache,
    ) {
    }

    /**
     * Retrieves a paginated list of tournaments.
     *
     * @param string|null $game        Game ID (e.g., PTCG, VGC)
     * @param string|null $format      Format ID (e.g., STANDARD)
     * @param int|null    $organizerId Organization ID
     * @param int         $limit       Number of tournaments per page (default: 50)
     * @param int         $page        Page number (default: 1)
     *
     * @throws LimitlessTcgException
     */
    public function getTournaments(
        ?string $game = null,
        ?string $format = null,
        ?int $organizerId = null,
        int $limit = 50,
        int $page = 1,
    ): PaginatedTournaments {
        $cacheKey = $this->buildCacheKey('tournaments', [
            'game' => $game,
            'format' => $format,
            'organizerId' => $organizerId,
            'limit' => $limit,
            'page' => $page,
        ]);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($game, $format, $organizerId, $limit, $page): PaginatedTournaments {
            $item->expiresAfter(self::CACHE_TTL_TOURNAMENTS);

            $query = array_filter([
                'game' => $game,
                'format' => $format,
                'organizerId' => $organizerId,
                'limit' => $limit,
                'page' => $page,
            ], fn ($value): bool => null !== $value);

            $response = $this->limitlessClient->request('GET', 'tournaments', [
                'query' => $query,
            ]);

            $statusCode = $response->getStatusCode();
            if (Response::HTTP_TOO_MANY_REQUESTS === $statusCode) {
                throw RateLimitException::create();
            }
            if (Response::HTTP_OK !== $statusCode) {
                throw LimitlessTcgException::fromHttpError($statusCode, $response->getContent(false));
            }

            try {
                /** @var list<array{id: string, game: string, format: string, name: string, date: string, players: int}> $data */
                $data = $response->toArray();
            } catch (\Throwable $e) {
                throw LimitlessTcgException::fromDeserializationError($e->getMessage(), $e);
            }

            $tournaments = array_map(
                fn (array $item): Tournament => Tournament::fromArray($item),
                $data,
            );

            return new PaginatedTournaments($tournaments, $page, $limit);
        });
    }

    /**
     * Retrieves detailed information about a specific tournament.
     *
     * @param string $tournamentId The unique tournament ID
     *
     * @throws LimitlessTcgException
     * @throws RateLimitException
     */
    public function getTournamentDetails(string $tournamentId): TournamentDetails
    {
        $cacheKey = $this->buildCacheKey('details', ['id' => $tournamentId]);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($tournamentId): TournamentDetails {
            $item->expiresAfter(self::CACHE_TTL_DETAILS);

            $response = $this->limitlessClient->request('GET', sprintf('tournaments/%s/details', $tournamentId));

            $statusCode = $response->getStatusCode();
            if (Response::HTTP_TOO_MANY_REQUESTS === $statusCode) {
                throw RateLimitException::create();
            }
            if (Response::HTTP_OK !== $statusCode) {
                throw LimitlessTcgException::fromHttpError($statusCode, $response->getContent(false));
            }

            try {
                /** @var array{id: string, game: string, format?: string|null, name: string, date: string, players: int, organizer: array{id: int, name: string, logo?: string|null}, platform?: string|null, decklists: bool, isPublic: bool, isOnline: bool, phases: list<array{phase: int, type: string, rounds: int, mode: string}>} $data */
                $data = $response->toArray();
            } catch (\Throwable $e) {
                throw LimitlessTcgException::fromDeserializationError($e->getMessage(), $e);
            }

            return TournamentDetails::fromArray($data);
        });
    }

    /**
     * Retrieves the standings for a specific tournament.
     *
     * @param string $tournamentId The unique tournament ID
     *
     * @return list<TournamentStanding>
     *
     * @throws LimitlessTcgException
     * @throws RateLimitException
     */
    public function getTournamentStandings(string $tournamentId): array
    {
        $cacheKey = $this->buildCacheKey('standings', ['id' => $tournamentId]);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($tournamentId): array {
            $item->expiresAfter(self::CACHE_TTL_STANDINGS);

            $response = $this->limitlessClient->request('GET', sprintf('tournaments/%s/standings', $tournamentId));

            $statusCode = $response->getStatusCode();
            if (Response::HTTP_TOO_MANY_REQUESTS === $statusCode) {
                throw RateLimitException::create();
            }
            if (Response::HTTP_OK !== $statusCode) {
                throw LimitlessTcgException::fromHttpError($statusCode, $response->getContent(false));
            }

            try {
                /** @var list<array{player: string, name: string, country: string, placing: int, record: array{wins: int, losses: int, ties: int}, decklist?: array<string, mixed>|null, deck?: array{id: string, name: string, icons: list<string>}|null, drop?: int|null}> $data */
                $data = $response->toArray();
            } catch (\Throwable $e) {
                throw LimitlessTcgException::fromDeserializationError($e->getMessage(), $e);
            }

            return array_map(
                fn (array $item): TournamentStanding => TournamentStanding::fromArray($item),
                $data,
            );
        });
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildCacheKey(string $prefix, array $params): string
    {
        $filteredParams = array_filter($params, fn ($value): bool => null !== $value);
        ksort($filteredParams);

        return sprintf('limitless_tcg_%s_%s', $prefix, md5(serialize($filteredParams)));
    }
}
