<?php

declare(strict_types=1);

namespace PimUpsertProductTool;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpsertProductCommand extends Command
{
    protected static $defaultName = 'run';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello world');

        return Command::SUCCESS;
    }
}
