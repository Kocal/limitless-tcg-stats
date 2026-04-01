<?php

namespace App\Limitless\Command;

use App\Limitless\LimitlessTcgClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'limitless:tournaments:standings',
    description: 'Show standings for a specific tournament',
)]
final class ShowTournamentStandingsCommand extends Command
{
    public function __construct(
        private readonly LimitlessTcgClient $client,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('tournament-id', InputArgument::REQUIRED, 'The tournament ID')
            ->addOption('top', 't', InputOption::VALUE_REQUIRED, 'Show only top N players', 10)
            ->addOption('country', 'c', InputOption::VALUE_REQUIRED, 'Filter by country code (e.g., FR, US, JP)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tournamentId = $input->getArgument('tournament-id');
        $top = (int) $input->getOption('top');
        $countryFilter = $input->getOption('country');

        $io->title(sprintf('Limitless TCG - Tournament Standings (%s)', $tournamentId));

        try {
            $standings = $this->client->getTournamentStandings($tournamentId);
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to fetch standings: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        if ([] === $standings) {
            $io->warning('No standings found for this tournament.');

            return Command::SUCCESS;
        }

        // Apply country filter if specified
        if ($countryFilter) {
            $countryFilter = strtoupper($countryFilter);
            $standings = array_filter(
                $standings,
                fn ($s) => null !== $s->country && strtoupper($s->country) === $countryFilter,
            );
            $standings = array_values($standings);

            if ([] === $standings) {
                $io->warning(sprintf('No players found from country: %s', $countryFilter));

                return Command::SUCCESS;
            }

            $io->comment(sprintf('Filtered by country: %s (%d players)', $countryFilter, count($standings)));
        }

        // Limit results
        $displayedStandings = array_slice($standings, 0, $top);

        $rows = [];
        foreach ($displayedStandings as $standing) {
            $record = sprintf('%d-%d-%d', $standing->record->wins, $standing->record->losses, $standing->record->ties);
            $winRate = sprintf('%.1f%%', $standing->record->getWinRate() * 100);
            $deckName = $standing->deck?->name ?? '-';
            $status = $standing->hasDropped() ? sprintf('Dropped R%d', $standing->drop) : '';

            $rows[] = [
                $standing->placing ?? '-',
                $standing->name,
                $standing->country ?? '-',
                $record,
                $winRate,
                mb_substr($deckName, 0, 25).(mb_strlen($deckName) > 25 ? '...' : ''),
                $status,
            ];
        }

        $io->table(
            ['#', 'Player', 'Country', 'Record', 'Win%', 'Deck', 'Status'],
            $rows,
        );

        $io->info(sprintf(
            'Showing %d of %d players',
            count($displayedStandings),
            count($standings),
        ));

        // Show deck distribution for top players
        $deckCounts = [];
        foreach ($displayedStandings as $standing) {
            $deckName = $standing->deck?->name ?? 'Unknown';
            $deckCounts[$deckName] = ($deckCounts[$deckName] ?? 0) + 1;
        }
        arsort($deckCounts);

        if (count($deckCounts) > 1) {
            $io->section('Deck Distribution (displayed players)');
            $deckRows = [];
            foreach ($deckCounts as $deck => $count) {
                $percentage = sprintf('%.1f%%', ($count / count($displayedStandings)) * 100);
                $deckRows[] = [$deck, $count, $percentage];
            }
            $io->table(['Deck', 'Count', '%'], $deckRows);
        }

        return Command::SUCCESS;
    }
}
