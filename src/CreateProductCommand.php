<?php

declare(strict_types=1);

namespace PimUpsertProductTool;

use Akeneo\Pim\ApiClient\Exception\UnprocessableEntityHttpException;
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
        $this->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of products to generate', 0);
        $this->addOption('family', null, InputOption::VALUE_OPTIONAL, 'Family of the products');
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

        $i = 0;
        do {
            $i++;
            $uuid = Uuid::uuid4();

            $family = $this->getFamily($families, $input->getOption('family'));
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
            try {
                $client->getProductUuidApi()->upsert($uuid->toString(), $data);
            } catch (UnprocessableEntityHttpException $e) {
                print_r($e->getMessage());
                print_r($data);
                print_r($e->getResponseErrors());

                throw $e;
            }
            $output->writeln('<info>[' . $i . '] Product created: ' . $uuid->toString() . '</info>');
            sleep(1);
        } while ($numberOfProductsToGenerate <= 0 || $i < $numberOfProductsToGenerate);

        return Command::SUCCESS;
    }

    private function getFamily(array $families, string|null $familyCode): array
    {
        if (null !== $familyCode) {
            foreach ($families as $family) {
                if ($family['code'] === 'mas_test') {
                    return $family;
                }
            }
        }

        $family = $families[rand(0, \count($families) - 1)];

        return $family;
    }
}
