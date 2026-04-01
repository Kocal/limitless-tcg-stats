<?php

namespace App\Command;

use App\Limitless\Dto\Tournament;
use App\Service\PlayerStatsCollector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'limitless:stats:collect',
    description: 'Collect tournament statistics for players matching a filter (default: FrogEX)',
)]
final class CollectPlayerStatsCommand extends Command
{
    public function __construct(
        private readonly PlayerStatsCollector $collector,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Player name filter (case-insensitive)', 'FrogEX')
            ->addOption('game', 'g', InputOption::VALUE_REQUIRED, 'Filter by game (e.g., PTCG, VGC)')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Filter by format (e.g., STANDARD)')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of tournaments to scan')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without persisting data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filter = $input->getOption('filter');
        $game = $input->getOption('game');
        $format = $input->getOption('format');
        $limit = $input->getOption('limit');
        $dryRun = $input->getOption('dry-run');

        // Validate and convert limit to int
        $maxTournaments = null;
        if (null !== $limit) {
            if (!ctype_digit($limit) || (int) $limit <= 0) {
                $io->error('The --limit option must be a positive integer.');

                return Command::INVALID;
            }
            $maxTournaments = (int) $limit;
        }

        $io->title('Limitless TCG - Player Stats Collector');

        $filterInfo = [];
        $filterInfo[] = \sprintf('Player filter: "%s"', $filter);
        if ($game) {
            $filterInfo[] = \sprintf('Game: %s', $game);
        }
        if ($format) {
            $filterInfo[] = \sprintf('Format: %s', $format);
        }
        if (null !== $maxTournaments) {
            $filterInfo[] = \sprintf('Limit: %d tournaments', $maxTournaments);
        }
        if ($dryRun) {
            $filterInfo[] = 'DRY RUN MODE - No data will be persisted';
        }

        $io->comment($filterInfo);

        if ($dryRun) {
            $io->warning('Dry run mode is not yet implemented. Aborting.');

            return Command::SUCCESS;
        }

        $io->section('Scanning tournaments...');

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat(' %current% tournaments scanned [%bar%] %message%');
        $progressBar->setMessage('Starting...');
        if (null !== $maxTournaments) {
            $progressBar->setMaxSteps($maxTournaments);
        }
        $progressBar->start();

        $matchingTournaments = 0;

        try {
            $stats = $this->collector->collect(
                playerFilter: $filter,
                game: $game,
                format: $format,
                maxTournaments: $maxTournaments,
                onTournamentProcessed: function (Tournament $tournament, array $tournamentStats) use ($progressBar, &$matchingTournaments): void {
                    $progressBar->advance();

                    if ($tournamentStats['players_found'] > 0) {
                        ++$matchingTournaments;
                        $progressBar->setMessage(\sprintf(
                            'Found %d player(s) in "%s"',
                            $tournamentStats['players_found'],
                            mb_substr($tournament->name, 0, 30)
                        ));
                    } else {
                        $progressBar->setMessage(\sprintf(
                            'Scanning: %s',
                            mb_substr($tournament->name, 0, 40)
                        ));
                    }
                },
            );
        } catch (\Throwable $e) {
            $progressBar->finish();
            $io->newLine(2);
            $io->error(\sprintf('Failed to collect stats: %s', $e->getMessage()));
            $this->getApplication()->renderThrowable($e, $io);

            return Command::FAILURE;
        }

        $progressBar->setMessage('Done!');
        $progressBar->finish();
        $io->newLine(2);

        $io->section('Summary');

        $io->definitionList(
            ['Tournaments scanned' => (string) $stats['tournaments_scanned']],
            ['Tournaments with matching players' => (string) $matchingTournaments],
            ['Unique players found' => (string) $stats['players_found']],
            ['New results persisted' => (string) $stats['results_persisted']],
            ['Results updated' => (string) $stats['results_updated']],
        );

        if ($stats['results_persisted'] > 0 || $stats['results_updated'] > 0) {
            $io->success(\sprintf(
                'Successfully collected stats for players matching "%s"!',
                $filter
            ));
        } else {
            $io->warning(\sprintf(
                'No players matching "%s" were found in any tournament.',
                $filter
            ));
        }

        return Command::SUCCESS;
    }
}
