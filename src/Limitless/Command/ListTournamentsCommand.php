<?php

namespace App\Limitless\Command;

use App\Limitless\LimitlessTcgClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'limitless:tournaments:list',
    description: 'List tournaments from Limitless TCG API',
)]
final class ListTournamentsCommand extends Command
{
    public function __construct(
        private readonly LimitlessTcgClient $client,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('game', 'g', InputOption::VALUE_REQUIRED, 'Filter by game (e.g., PTCG, VGC)')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Filter by format (e.g., STANDARD)')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of tournaments to fetch', 10)
            ->addOption('page', 'p', InputOption::VALUE_REQUIRED, 'Page number', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $game = $input->getOption('game');
        $format = $input->getOption('format');
        $limit = (int) $input->getOption('limit');
        $page = (int) $input->getOption('page');

        $io->title('Limitless TCG - Tournaments');

        if ($game || $format) {
            $io->comment(sprintf('Filters: game=%s, format=%s', $game ?? 'all', $format ?? 'all'));
        }

        try {
            $result = $this->client->getTournaments(
                game: $game,
                format: $format,
                limit: $limit,
                page: $page,
            );
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to fetch tournaments: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        if ($result->isEmpty()) {
            $io->warning('No tournaments found.');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($result as $tournament) {
            $rows[] = [
                $tournament->id,
                $tournament->game,
                $tournament->format ?? '-',
                mb_substr($tournament->name, 0, 40).(mb_strlen($tournament->name) > 40 ? '...' : ''),
                $tournament->date->format('Y-m-d H:i'),
                $tournament->players,
            ];
        }

        $io->table(
            ['ID', 'Game', 'Format', 'Name', 'Date', 'Players'],
            $rows,
        );

        $io->info(sprintf(
            'Page %d | Showing %d tournaments | %s',
            $result->page,
            count($result),
            $result->hasMore() ? 'More results available (use --page to navigate)' : 'No more results',
        ));

        return Command::SUCCESS;
    }
}
