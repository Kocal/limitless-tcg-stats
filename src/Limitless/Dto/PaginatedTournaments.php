<?php

namespace App\Limitless\Dto;

/**
 * @implements \IteratorAggregate<int, Tournament>
 */
final readonly class PaginatedTournaments implements \IteratorAggregate, \Countable
{
    /**
     * @param list<Tournament> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $limit,
    ) {
    }

    /**
     * Returns true if there are potentially more results on the next page.
     * This is a heuristic based on whether the current page returned the maximum number of items.
     */
    public function hasMore(): bool
    {
        return count($this->items) >= $this->limit;
    }

    public function getNextPage(): int
    {
        return $this->page + 1;
    }

    public function isEmpty(): bool
    {
        return [] === $this->items;
    }

    /**
     * @return \Traversable<int, Tournament>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
