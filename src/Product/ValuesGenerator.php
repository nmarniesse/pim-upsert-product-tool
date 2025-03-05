<?php

declare(strict_types=1);

/*
 * This file is part of the Akeneo PIM Enterprise Edition.
 *
 * (c) 2024 Akeneo SAS (https://www.akeneo.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PimUpsertProductTool\Product;

use Akeneo\Pim\ApiClient\AkeneoPimClient;
use Faker\Generator;

final class ValuesGenerator
{
    private array $attributeOptionsByAttribute = [];

    public function generateValues(
        AkeneoPimClient $client,
        Generator $faker,
        array $channels,
        array $indexedAttributes,
        array $productFamily,
    ): array {
        $values = [];
        foreach ($productFamily['attributes'] as $attributeCode) {
            $attribute = $indexedAttributes[$attributeCode] ?? null;
            if (null === $attribute) {
                continue;
            }

            $productChannels = [['code' => null, 'locales' => $channels[0]['locales']]];
            if ($attribute['scopable']) {
                $productChannels = $channels;
            }

            foreach ($productChannels as $channel) {
                $localeCodes = [null];
                if ($attribute['localizable']) {
                    $channel = $channel ?? $channels[0];
                    $localeCodes = $channel['locales'];
                    $value['locale'] = $localeCodes[rand(0, \count($localeCodes) - 1)];
                    $localeCodes = $channel['locales'];
                }

                foreach ($localeCodes as $localeCode) {
                    $value = ['scope' => $channel['code'], 'locale' => $localeCode];
                    $value['data'] = match ($attribute['type']) {
                        'pim_catalog_identifier' => 'FAKER_' . \strtoupper($faker->unique()->uuid()),
                        'pim_catalog_text' => $faker->text(50),
                        'pim_catalog_textarea' => $faker->text(),
                        'pim_catalog_number' => $faker->randomNumber(),
                        'pim_catalog_boolean' => $faker->boolean(),
                        'pim_catalog_date' => $faker->date(),
                        'pim_catalog_simpleselect' => $this->generateOptionValue($client, $attributeCode, false),
                        'pim_catalog_multiselect' => $this->generateOptionValue($client, $attributeCode, true),
                        default => null,
                    };

                    if (null === $value['data']) {
                        continue;
                    }

                    $values[$attributeCode][] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * @return string|string[]|null
     */
    private function generateOptionValue(AkeneoPimClient $client, string $attributeCode, bool $multi): string|array|null
    {
        if (!\array_key_exists($attributeCode, $this->attributeOptionsByAttribute)) {
            $this->attributeOptionsByAttribute[$attributeCode] = [];
            $attributeOptions = $client->getAttributeOptionApi()->all($attributeCode);
            $count = 0;
            foreach ($attributeOptions as $attributeOption) {
                $this->attributeOptionsByAttribute[$attributeCode][] = $attributeOption;
                if (++$count >= 100) {
                    break;
                }
            }
        }

        if ([] === $this->attributeOptionsByAttribute[$attributeCode]) {
            return null;
        }

        $value = $this->attributeOptionsByAttribute[$attributeCode][\rand(0, \count($this->attributeOptionsByAttribute[$attributeCode]) - 1)]['code'];

        return $multi ? [$value] : $value;
    }
}
