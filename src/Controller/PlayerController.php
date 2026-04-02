<?php

namespace App\Controller;

use App\Repository\PlayerRepository;
use App\Repository\PlayerTournamentResultRepository;
use App\ValueObject\PlayerId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/players', name: 'player_')]
final class PlayerController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(PlayerRepository $playerRepository): Response
    {
        $players = $playerRepository->findAllWithMinimumTournaments(3);

        return $this->render('player/index.html.twig', [
            'players' => $players,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        string $id,
        PlayerRepository $playerRepository,
        PlayerTournamentResultRepository $resultRepository,
    ): Response {
        try {
            $playerId = PlayerId::fromString($id);
        } catch (\InvalidArgumentException) {
            throw new NotFoundHttpException('Player not found.');
        }

        $player = $playerRepository->findById($playerId);

        if (null === $player) {
            throw new NotFoundHttpException('Player not found.');
        }

        $results = $resultRepository->findByPlayer($player);
        $stats = $resultRepository->getPlayerStats($player);

        return $this->render('player/show.html.twig', [
            'player' => $player,
            'results' => $results,
            'stats' => $stats,
        ]);
    }
}
