<?php

namespace App\Entity;

use App\Repository\PlayerTournamentResultRepository;
use App\ValueObject\PlayerTournamentResultId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlayerTournamentResultRepository::class)]
#[ORM\Table(name: 'player_tournament_result')]
#[ORM\UniqueConstraint(name: 'unique_player_tournament', columns: ['player_id', 'tournament_id'])]
#[ORM\HasLifecycleCallbacks]
class PlayerTournamentResult
{
    #[ORM\Id]
    #[ORM\Column(type: 'player_tournament_result_id')]
    private PlayerTournamentResultId $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Tournament::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tournament $tournament;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $finalPlacing = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $wins = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $losses = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $ties = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deckName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deckId = null;

    /**
     * @var list<string>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $deckIcons = null;

    #[ORM\Column]
    private bool $dropped = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(PlayerTournamentResultId $id, Player $player, Tournament $tournament)
    {
        $this->id = $id;
        $this->player = $player;
        $this->tournament = $tournament;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): PlayerTournamentResultId
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getTournament(): Tournament
    {
        return $this->tournament;
    }

    public function getFinalPlacing(): ?int
    {
        return $this->finalPlacing;
    }

    public function setFinalPlacing(?int $finalPlacing): static
    {
        $this->finalPlacing = $finalPlacing;

        return $this;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function setWins(int $wins): static
    {
        $this->wins = $wins;

        return $this;
    }

    public function getLosses(): int
    {
        return $this->losses;
    }

    public function setLosses(int $losses): static
    {
        $this->losses = $losses;

        return $this;
    }

    public function getTies(): int
    {
        return $this->ties;
    }

    public function setTies(int $ties): static
    {
        $this->ties = $ties;

        return $this;
    }

    public function getDeckName(): ?string
    {
        return $this->deckName;
    }

    public function setDeckName(?string $deckName): static
    {
        $this->deckName = $deckName;

        return $this;
    }

    public function getDeckId(): ?string
    {
        return $this->deckId;
    }

    public function setDeckId(?string $deckId): static
    {
        $this->deckId = $deckId;

        return $this;
    }

    /**
     * Returns the deck icons (maximum 2).
     *
     * @return list<string>
     */
    public function getDeckIcons(): array
    {
        return $this->deckIcons ?? [];
    }

    /**
     * Sets the deck icons (keeps only the first 2).
     *
     * @param list<string>|null $deckIcons
     */
    public function setDeckIcons(?array $deckIcons): static
    {
        $this->deckIcons = null !== $deckIcons ? \array_slice($deckIcons, 0, 2) : null;

        return $this;
    }

    /**
     * Returns the Smogon minisprite URLs for the deck icons.
     *
     * @return list<string>
     */
    public function getDeckIconUrls(): array
    {
        return array_map(
            static fn (string $icon): string => \sprintf('https://www.smogon.com/forums/media/minisprites/%s.png', $icon),
            $this->getDeckIcons(),
        );
    }

    public function isDropped(): bool
    {
        return $this->dropped;
    }

    public function setDropped(bool $dropped): static
    {
        $this->dropped = $dropped;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getRecord(): string
    {
        return \sprintf('%d-%d-%d', $this->wins, $this->losses, $this->ties);
    }

    public function getTotalGames(): int
    {
        return $this->wins + $this->losses + $this->ties;
    }

    public function getWinRate(): float
    {
        $total = $this->getTotalGames();

        return $total > 0 ? $this->wins / $total : 0.0;
    }
}
