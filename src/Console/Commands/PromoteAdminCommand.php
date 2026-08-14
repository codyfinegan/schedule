<?php

declare(strict_types=1);

namespace Schedule\Console\Commands;

use Schedule\Models\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'admin:promote', description: 'Create (if needed), approve, and promote a user to admin by email.')]
final class PromoteAdminCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email address of the user to promote.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = strtolower(trim((string) $input->getArgument('email')));

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $user = new User(['email' => $email]);
        }

        $user->is_admin = true;
        $user->is_approved = true;
        $user->save();

        $output->writeln(sprintf('<info>%s is now an approved admin (user #%d).</info>', $email, $user->id));

        return Command::SUCCESS;
    }
}
