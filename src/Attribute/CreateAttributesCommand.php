<?php

declare(strict_types=1);

namespace PimUpsertProductTool\Attribute;

use Akeneo\Pim\ApiClient\Exception\UnprocessableEntityHttpException;
use PimUpsertProductTool\ClientFactory;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-attributes')]
final class CreateAttributesCommand extends Command
{
    private array $availableTypes = [
        'pim_catalog_text',
        'pim_catalog_number',
        'pim_catalog_boolean',
        'pim_catalog_date',
        'pim_catalog_price_collection',
        'pim_catalog_multiselect',
        'pim_catalog_simpleselect',
        'pim_catalog_file',
        'pim_catalog_image',
    ];

    protected function configure(): void
    {
        $this->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of attributes to create', 9999);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numberOfAttributesToCreate = (int) $input->getOption('count');
        $client = ClientFactory::build();

        $output->writeln('');
        $output->writeln('Begin to generate attributes...');

        $i = 0;
        do {
            $i++;
            $code = \preg_replace('/-/', '_', Uuid::uuid4()->toString());
            $type = $this->availableTypes[\array_rand($this->availableTypes)];

            $data = [
                'type' => $type,
                'group' => 'other',
                'scopable' => false,
                'localizable' => false,
                'unique' => false,
                'useable_as_grid_filter' => true,
            ];

            $this->addOptionForType($data, $type);

            try {
                $client->getAttributeApi()->create($code, $data);
                $output->writeln('<info>[' . $i . '] Attribute create: ' . $code . '</info>');
            } catch (UnprocessableEntityHttpException $e) {
                print_r($e->getMessage());
                print_r($data);
                print_r($e->getResponseErrors());

                throw $e;
            }
        } while ($i < $numberOfAttributesToCreate);

        return Command::SUCCESS;
    }

    private function addOptionForType(array &$data, string $type): void
    {
        switch ($type) {
            case 'pim_catalog_number':
                $data['decimals_allowed'] = false;
                $data['negative_allowed'] = false;
                break;
            case 'pim_catalog_price_collection':
                $data['decimals_allowed'] = false;
                break;
        }
    }
}
