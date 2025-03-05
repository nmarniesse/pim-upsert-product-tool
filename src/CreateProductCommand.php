<?php

declare(strict_types=1);

namespace PimUpsertProductTool;

use Akeneo\Pim\ApiClient\AkeneoPimClient;
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
    // Set to 1 to use single upsert API endpoint
    private const PRODUCTS_BY_BATCH = 100;

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

        $indexedFamilies = [];
        foreach ($client->getFamilyApi()->all() as $family) {
            $indexedFamilies[$family['code']] = $family;
        }
        $output->writeln(\sprintf('<info>%d families found.</info>', \count($indexedFamilies)));

        $indexedAttributes = [];
        foreach ($client->getAttributeApi()->all() as $attribute) {
            $indexedAttributes[$attribute['code']] = $attribute;
        }
        $output->writeln(\sprintf('<info>%d attributes found.</info>', \count($indexedAttributes)));

        $output->writeln('');
        $output->writeln('Begin to generate products...');
        $valuesGenerator = new ValuesGenerator();

        $batchProducts = [];
        $i = 0;
        do {
            $i++;
            $uuid = Uuid::uuid4();

            $family = $this->getFamily($indexedFamilies, $input->getOption('family'));
            $data = [
                'uuid' => $uuid->toString(),
                'identifier' => $uuid->toString(),
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
                $batchProducts[] = $data;

                if (\count($batchProducts) >= self::PRODUCTS_BY_BATCH) {
                    $this->upsertProducts($client, $output, $batchProducts);
                    $output->writeln('<info>[' . $i . '] Products created</info>');
                    $batchProducts = [];
                }
            } catch (UnprocessableEntityHttpException $e) {
                print_r($e->getMessage());
                print_r($data);
                print_r($e->getResponseErrors());

                throw $e;
            }
        } while ($numberOfProductsToGenerate <= 0 || $i < $numberOfProductsToGenerate);

        if (\count($batchProducts) > 0) {
            $this->upsertProducts($client, $output, $batchProducts);
            $output->writeln('<info>[' . $i . '] Products created</info>');
        }

        return Command::SUCCESS;
    }

    private function upsertProducts(AkeneoPimClient $client, OutputInterface $output, array $products): void
    {
        $responses = $client->getProductUuidApi()->upsertList($products);
        foreach ($responses as $response) {
            if ($response['status_code'] >= 400) {
                $output->writeln('<error>' . $response['status_code'] . '</error>');
                $output->writeln(print_r($response, true));
            }
        }
    }

    private function getFamily(array $families, string|null $familyCode): array
    {
        if (null !== $familyCode) {
            $family = $families[$familyCode] ?? null;
            if (null === $family) {
                throw new \Exception('Could not find family');
            }

            return $family;
        }

        $family = \array_values($families)[rand(0, \count($families) - 1)];

        return $family;
    }
}
