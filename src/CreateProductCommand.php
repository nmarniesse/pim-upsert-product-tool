<?php

declare(strict_types=1);

namespace PimUpsertProductTool;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Faker\Factory;
use Faker\Generator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-products')]
final class CreateProductCommand extends Command
{
    private array $channels;
    private array $families;
    private array $attributes;

    protected function configure(): void
    {
        $this->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of products to generate', 9999);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $_ENV['HOST'];
        $clientId = $_ENV['CLIENT_ID'];
        $secret = $_ENV['CLIENT_SECRET'];
        $username = $_ENV['USERNAME'];
        $password = $_ENV['PASSWORD'];

        $faker = Factory::create();
        $numberOfProductsToGenerate = (int) $input->getOption('count');

        $builder = new AkeneoPimClientBuilder($host);
        $client = $builder->buildAuthenticatedByPassword($clientId, $secret, $username, $password);

        $this->channels = \iterator_to_array($client->getChannelApi()->all());
        $output->writeln(\sprintf('<info>%d channels found.</info>', \count($this->channels)));
        print_r($this->channels);

        $this->families = \iterator_to_array($client->getFamilyApi()->all());
        $output->writeln(\sprintf('<info>%d families found.</info>', \count($this->families)));

        $attributes = \iterator_to_array($client->getAttributeApi()->all());
        foreach ($attributes as $attribute) {
            $this->attributes[$attribute['code']] = $attribute;
        }
        $output->writeln(\sprintf('<info>%d attributes found.</info>', \count($this->attributes)));

        $output->writeln('');
        $output->writeln('Begin to generate products...');
        for ($i = 1; $i <= $numberOfProductsToGenerate; $i++) {
            $uuid = Uuid::uuid4();

            $data = $this->generateProductData($faker);
            $client->getProductApi()->upsert($uuid->toString(), $data);
            $output->writeln('<info>[' . $i . '] Product created: ' . $uuid->toString() . '</info>');
            print_r($data);
        }

        return Command::SUCCESS;
    }

    private function generateProductData(Generator $faker): array
    {
        $family = $this->families[rand(0, \count($this->families) - 1)];
        $data = [
            'family' => $family['code'],
        ];

        foreach ($family['attributes'] as $attributeCode) {
            $attribute = $this->attributes[$attributeCode] ?? null;
            if (null === $attribute) {
                continue;
            }

            $channels = [['code' => null, 'locales' => $this->channels[0]['locales']]];
            if ($attribute['scopable']) {
                $channels = $this->channels;
            }

            foreach ($channels as $channel) {
                $localeCodes = [null];
                if ($attribute['localizable']) {
                    $channel = $channel ?? $this->channels[0];
                    $localeCodes = $channel['locales'];
                    $value['locale'] = $localeCodes[rand(0, \count($localeCodes) - 1)];
                    $localeCodes = $channel['locales'];
                }

                foreach ($localeCodes as $localeCode) {
                    $value = ['scope' => $channel['code'], 'locale' => $localeCode];
                    $value['data'] = match ($attribute['type']) {
                        'pim_catalog_identifier' => 'FAKER_' . \strtoupper($faker->unique()->uuid()),
                        'pim_catalog_text', 'pim_catalog_textarea' => $faker->text(),
                        'pim_catalog_number' => $faker->randomNumber(),
                        'pim_catalog_boolean' => $faker->boolean(),
                        'pim_catalog_date' => $faker->date(),
                        default => null,
                    };

                    if (null === $value['data']) {
                        continue;
                    }

                    $data['values'][$attributeCode][] = $value;
                }
            }
        }

        return $data;
    }
}
