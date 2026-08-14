<?php

declare(strict_types=1);

namespace Schedule\Console\Commands;

use Schedule\Models\RecurringSchedule;
use Schedule\Services\BlockMaterializer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'schedule:generate-blocks', description: 'Materializes active recurring schedules into game_blocks out to the rolling horizon.')]
final class GenerateBlocksCommand extends Command
{
    public function __construct(
        private readonly BlockMaterializer $materializer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schedules = RecurringSchedule::query()->where('is_active', true)->get();

        $total = 0;
        foreach ($schedules as $schedule) {
            $created = $this->materializer->materialize($schedule);
            $total += $created;
            $output->writeln(sprintf('Schedule #%d: created %d block(s).', $schedule->id, $created));
        }

        $output->writeln(sprintf('<info>Done. %d block(s) created across %d schedule(s).</info>', $total, count($schedules)));

        return Command::SUCCESS;
    }
}
