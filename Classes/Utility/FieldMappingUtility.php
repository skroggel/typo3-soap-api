<?php
namespace Madj2k\SoapApi\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class FieldMappingUtility
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_CoreExtended
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FieldMappingUtility
{

    /**
     * Returns mappingArray for given table
     *
     * @param array $mappingList
     * @param string $table
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public static function getMapping(array $mappingList, string $table): array
    {
        if (! $table) {
            throw new \Madj2k\SoapApi\Exception(
                'No tableName set',
                1690524566
            );
        }

        if (! $GLOBALS['TCA'][$table]) {
            throw new \Madj2k\SoapApi\Exception(
                sprintf('There is no TCA-configuration available for table "%s"', $table),
                1690524567
            );
        }

        if (! $mappingList[$table] ) {
            throw new \Madj2k\SoapApi\Exception(
                sprintf('There is no fieldMapping-configuration available for table "%s"', $table),
                1690524568
            );
        }

        $propertyMappingArray = [];
        foreach ($mappingList[$table] as $extensionKey => $fieldList) {

            if (
                ($extensionKey == 'core')
                || (ExtensionManagementUtility::isLoaded($extensionKey))
            ) {

                foreach ($fieldList as $field => $fieldDefinition) {

                    if (
                        (! isset($fieldDefinition['key']))
                        || (! $fieldDefinition['key'])
                    ){
                        throw new \Madj2k\SoapApi\Exception(
                            sprintf('Missing key-definition in fieldMapping-configuration for field "%s" in table "%s"',
                                $field,
                                $table),
                            1690524573
                        );
                    }

                    if (
                        (! isset($fieldDefinition['type']))
                        || (! $fieldDefinition['type'])
                    ){
                        throw new \Madj2k\SoapApi\Exception(
                            sprintf('Missing type-definition in fieldMapping-configuration for field "%s" in table "%s"',
                                $field,
                                $table),
                            1690524574
                        );
                    }

                    $propertyMappingArray[$field] = $fieldDefinition;
                }
            }
        }

        if (! $propertyMappingArray) {
            throw new \Madj2k\SoapApi\Exception(
                sprintf(
                    'No valid field-mapping found for table "%s"',
                    $table),
                1690953438
            );
        }

        return $propertyMappingArray;
    }
}
