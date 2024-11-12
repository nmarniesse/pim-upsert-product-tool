<?php

declare(strict_types=1);

namespace PimUpsertProductTool;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Faker\Factory;
use PimUpsertProductTool\Product\ValuesGenerator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-products')]
final class CreateProductCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of products to generate', 9999);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create();
        $numberOfProductsToGenerate = (int) $input->getOption('count');
        $client = ClientFactory::build();

        $channels = \iterator_to_array($client->getChannelApi()->all());
        $output->writeln(\sprintf('<info>%d channels found.</info>', \count($channels)));

        $families = \iterator_to_array($client->getFamilyApi()->all());
        $output->writeln(\sprintf('<info>%d families found.</info>', \count($families)));

        $indexedAttributes = [];
        foreach ($client->getAttributeApi()->all() as $attribute) {
            $indexedAttributes[$attribute['code']] = $attribute;
        }
        $output->writeln(\sprintf('<info>%d attributes found.</info>', \count($indexedAttributes)));

        $output->writeln('');
        $output->writeln('Begin to generate products...');
        $valuesGenerator = new ValuesGenerator();
        for ($i = 1; $i <= $numberOfProductsToGenerate; $i++) {
            $uuid = Uuid::uuid4();

            $family = $families[rand(0, \count($families) - 1)];
            $data = [
                'family' => $family['code'],
                'values' => $valuesGenerator->generateValues(
                    $client,
                    $faker,
                    $channels,
                    $indexedAttributes,
                    $family
                )
            ];
            $client->getProductApi()->upsert($uuid->toString(), $data);
            $output->writeln('<info>[' . $i . '] Product created: ' . $uuid->toString() . '</info>');
//            print_r($data);
        }

        return Command::SUCCESS;
    }
}
