<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use App\ValueObject\LimitlessTournamentId;
use App\ValueObject\TournamentId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[ORM\Table(name: 'tournament')]
#[ORM\Index(columns: ['date'], name: 'idx_tournament_date')]
#[ORM\Index(columns: ['game'], name: 'idx_tournament_game')]
#[ORM\HasLifecycleCallbacks]
class Tournament
{
    #[ORM\Id]
    #[ORM\Column(type: 'tournament_id')]
    private TournamentId $id;

    #[ORM\Column(type: 'limitless_tournament_id', length: 255, unique: true)]
    #[Assert\NotBlank]
    private LimitlessTournamentId $externalId;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private string $game;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $format = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $playerCount = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        TournamentId $id,
        LimitlessTournamentId $externalId,
        string $name,
        string $game,
        \DateTimeImmutable $date,
    ) {
        $this->id = $id;
        $this->externalId = $externalId;
        $this->name = $name;
        $this->game = $game;
        $this->date = $date;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): TournamentId
    {
        return $this->id;
    }

    public function getExternalId(): LimitlessTournamentId
    {
        return $this->externalId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getGame(): string
    {
        return $this->game;
    }

    public function setGame(string $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getPlayerCount(): int
    {
        return $this->playerCount;
    }

    public function setPlayerCount(int $playerCount): static
    {
        $this->playerCount = $playerCount;

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
}
