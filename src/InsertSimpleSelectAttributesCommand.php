<?php

declare(strict_types=1);

namespace PimUpsertProductTool;

use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:insert-simple-select-attributes')]
final class InsertSimpleSelectAttributesCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of products to update', 9999);
        $this->addOption('count-options', 'o', InputOption::VALUE_OPTIONAL, 'Number of products to update', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numberOfAttributesToInsert = (int) $input->getOption('count');
        $client = ClientFactory::build();

        $output->writeln('');
        $output->writeln('Begin to generate attributes...');

        $i = 0;
        do {
            $i++;
            $code = (Uuid::uuid4())->toString();

            $data = [
                'type' => 'pim_catalog_simpleselect',
                'group' => 'other',
                'scopable' => false,
                'localizable' => false,
                'unique' => 'false',
                'useable_as_grid_filter' => true,
            ];
            $client->getAttributeApi()->create($code, $data);
            $output->writeln('<info>[' . $i . '] Attribute updated: ' . $code . '</info>');
        } while ($i < $numberOfAttributesToInsert);

        return Command::SUCCESS;
    }
}
