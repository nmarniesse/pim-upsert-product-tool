<?php

declare(strict_types=1);

namespace PimUpsertProductTool;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:run')]
final class UpsertProductCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello world');

        $host = $_ENV['HOST'];
        $clientId = $_ENV['CLIENT_ID'];
        $secret = $_ENV['CLIENT_SECRET'];
        $username = $_ENV['USERNAME'];
        $password = $_ENV['PASSORD'];

        $builder = new AkeneoPimClientBuilder($host);
        $client = $builder->buildAuthenticatedByPassword($clientId, $secret, $username, $password);

        $families = $client->getFamilyApi()->all(10);

        $output->writeln(\sprintf('%d families found.', \count(\iterator_to_array($families))));

        return Command::SUCCESS;
    }
}
