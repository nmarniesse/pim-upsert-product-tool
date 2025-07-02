<?php

declare(strict_types=1);

namespace PimUpsertProductTool\Family;

use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use Akeneo\Pim\ApiClient\Exception\UnprocessableEntityHttpException;
use PimUpsertProductTool\ClientFactory;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-hierarchical-families')]
final class CreateHierarchicalFamiliesCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of families to create', 9999);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numberOfAttributesToCreate = (int) $input->getOption('count');
        $client = ClientFactory::build();

        $channelCodes = \array_map(
            static fn(array $channel): string => $channel['code'],
            \iterator_to_array($client->getChannelApi()->all())
        );
        $output->writeln(\sprintf('<info>%d channels found.</info>', \count($channelCodes)));

        $indexedAttributes = [];
        $attributeCodes = [];
        foreach ($client->getAttributeApi()->all() as $attribute) {
            $attributeCodes[] = $attribute['code'];
            $indexedAttributes[$attribute['code']] = $attribute;
        }
        $output->writeln(\sprintf('<info>%d attributes found.</info>', \count($indexedAttributes)));

        try {
            $isMasterExist = [] !== $client->getFamilyApi()->get('master');
        } catch (NotFoundHttpException) {
            $isMasterExist = false;
        }

        $output->writeln('');
        $output->writeln('Begin to generate families...');

        $createdFamilyCodes = [];
        $levelsByFamilyCodes = ['master' => 1];
        $i = 0;
        do {
            $i++;
            $code = !$isMasterExist ? 'master' : \preg_replace('/-/', '_', Uuid::uuid4()->toString());

            $attributeCodesForThisFamily = $this->getRandomAttributes($attributeCodes, \rand(100, 150));

            $parentCode = $isMasterExist ? 'master' : null;
            if ($this->matchPourcent(90) && [] !== $createdFamilyCodes) {
                $parentCode = $createdFamilyCodes[\array_rand($createdFamilyCodes)];
            }

            $requirements = [];
            if (null === $parentCode) {
                foreach ($channelCodes as $channelCode) {
                    $requirements[$channelCode] = $this->getRandomAttributes($attributeCodesForThisFamily, 10);
                }
            }


            $data = [
                'parent' => $parentCode,
                'attributes' => $attributeCodesForThisFamily,
                'attribute_requirements' => $requirements,
            ];

            try {
                $client->getFamilyApi()->create($code, $data);

                $level = null === $parentCode ? 1 : $levelsByFamilyCodes[$parentCode] + 1;
                $levelsByFamilyCodes[$code] = $level;
                $output->writeln('<info>[' . $i . '] Family create: ' . $code . ', parent: ' . $parentCode . ', level: ' . $level . '</info>');
                $createdFamilyCodes[] = $code;
                if ('master' === $code) {
                    $isMasterExist = true;
                }
            } catch (UnprocessableEntityHttpException $e) {
                print_r($e->getMessage());
                print_r($data);
                print_r($e->getResponseErrors());

                throw $e;
            }
        } while ($i < $numberOfAttributesToCreate);

        return Command::SUCCESS;
    }

    private function getRandomAttributes(array $attributeCodes, int $count): array
    {
        $randomAttributeCodes = [];
        for ($i = 0; $i < $count; $i++) {
            $randomAttributeCodes[] = $attributeCodes[\array_rand($attributeCodes)];
        }

        return \array_values(\array_unique($randomAttributeCodes));
    }

    private function matchPourcent(int $pourcent = 50): bool
    {
        $random = \rand(0, 100);

        return $random <= $pourcent;
    }
}
