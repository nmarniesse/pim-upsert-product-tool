<?php

declare(strict_types=1);

namespace PimUpsertProductTool;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Faker\Factory;
use PimUpsertProductTool\Product\ValuesGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:update-products')]
final class UpdateProductCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of products to update', 9999);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numberOfProductsToUpdate = (int) $input->getOption('count');
        $faker = Factory::create();
        $client = ClientFactory::build();

        $channels = \iterator_to_array($client->getChannelApi()->all());
        $output->writeln(\sprintf('<info>%d channels found.</info>', \count($channels)));

        $indexedFamily = [];
        foreach ($client->getFamilyApi()->all() as $family) {
            $indexedFamily[$family['code']] = $family;
        }
        $output->writeln(\sprintf('<info>%d families found.</info>', \count($indexedFamily)));

        $indexedAttributes = [];
        foreach ($client->getAttributeApi()->all() as $attribute) {
            $indexedAttributes[$attribute['code']] = $attribute;
        }
        $output->writeln(\sprintf('<info>%d attributes found.</info>', \count($indexedAttributes)));

        $products = $client->getProductUuidApi()->all();
        $productCount = 0;
        $productUuids = [];
        foreach ($products as $product) {
            if (null === $product['family']) {
                continue;
            }

            $productUuids[] = $product;
            if (++$productCount >= 1000) {
                break;
            }
        }

        $output->writeln('');
        $output->writeln('Begin to generate products...');
        $valuesGenerator = new ValuesGenerator();

        $i = 0;
        do {
            $i++;
            $product = $productUuids[\rand(0, \count($productUuids))];
            $family = $indexedFamily[$product['family']];

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
            $client->getProductApi()->upsert($product['uuid'], $data);
            $output->writeln('<info>[' . $i . '] Product updated: ' . $product['uuid'] . '</info>');
//            print_r($data);
        } while ($numberOfProductsToUpdate <= 0 || $i < $numberOfProductsToUpdate);

        return Command::SUCCESS;
    }
}
