<?php

namespace App\Controller;

use App\Repository\PlayerRepository;
use App\Repository\PlayerTournamentResultRepository;
use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(
        PlayerRepository $playerRepository,
        TournamentRepository $tournamentRepository,
        PlayerTournamentResultRepository $resultRepository,
    ): Response {
        return $this->render('home/index.html.twig', [
            'playerCount' => $playerRepository->count(),
            'tournamentCount' => $tournamentRepository->count(),
            'resultCount' => $resultRepository->count(),
            'recentResults' => $resultRepository->findRecentResults(5),
        ]);
    }
}
