<?php
namespace Madj2k\SoapApi\Tests\Integration\Utility;

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
 *
 */

use Madj2k\SoapApi\Utility\FieldMappingUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * FieldMappingUtilityTest
 *
 * @author Steffen Krogel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_SoapApi
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FieldMappingUtilityTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/FieldMappingUtilityTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/core_extended',
        'typo3conf/ext/soap_api', // has to be loaded !!!
    ];


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'filemetadata',
        'seo'
    ];


    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(self::FIXTURE_PATH . '/Database/Global.csv');

        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => [
                    'EXT:core_extended/Configuration/TypoScript/constants.typoscript',
                    'EXT:soap_api/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:core_extended/Configuration/TypoScript/setup.typoscript',
                    // 'EXT:soap_api/Configuration/TypoScript/setup.typoscript',
                    'EXT:soap_api/Tests/Integration/Utility/FieldMappingUtilityTest/Fixtures/Frontend/Configuration/Rootpage.typoscript',
                ]
            ]
        );

    }


    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function getMappingThrowsExceptionIfNoTableNameSet ()
    {
        /**
         * Scenario:
         *
         * Given setTableName has not been called before
         * When the method is called
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 1690524566
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690524566);

        $settings = $this->getSettings();
        FieldMappingUtility::getMapping($settings['fieldMapping'], '');

    }


    /**
     * @test
     * @throws \Exception
     */
    public function getMappingThrowsExceptionIfNoTcaConfiguration ()
    {
        /**
         * Scenario:
         *
         * Given a TCA-configuration for a table oes not exist
         * Given setTableName has been called with that tableName
         * When the method is called
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 1690524567
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690524567);

        $settings = $this->getSettings();
        FieldMappingUtility::getMapping($settings['fieldMapping'], 'non_existing_table');

    }


    /**
     * @test
     * @throws \Exception
     */
    public function getMappingThrowsExceptionIfNoFieldMappingConfiguration ()
    {
        /**
         * Scenario:
         *
         * Given a fieldMapping-configuration for a table does not exist
         * Given setTableName has been called with that tableName
         * When the method is called
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 1690524568
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690524568);

        $settings = $this->getSettings();
        FieldMappingUtility::getMapping($settings['fieldMapping'], 'be_users');

    }


    /**
     * @test
     * @throws \Exception
     */
    public function getMappingThrowsExceptionIfKeyDefinitionMissingInFieldMappingConfiguration ()
    {
        /**
         * Scenario:
         *
         * Given a fieldMapping-configuration for an existing table
         * Given that fieldMapping has field-definitions for the core
         * Given one of the field-definitions has no key-definition set
         * Given setTableName has been called with that tableName
         * When the method is called
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 169052473
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690524573);

        $settings = $this->getSettings();
        FieldMappingUtility::getMapping($settings['fieldMapping'], 'fe_groups');

    }

    /**
     * @test
     * @throws \Exception
     */
    public function getMappingThrowsExceptionIfTypeDefinitionMissingInFieldMappingConfiguration ()
    {
        /**
         * Scenario:
         *
         * Given a fieldMapping-configuration for an existing table
         * Given that fieldMapping has field-definitions for the core
         * Given one of the field-definitions has no type-definition set
         * Given setTableName has been called with that tableName
         * When the method is called
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 169052474
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690524574);

        $settings = $this->getSettings();
        FieldMappingUtility::getMapping($settings['fieldMapping'], 'sys_category');

    }


    /**
     * @test
     * @throws \Exception
     */
    public function getMappingThrowsExceptionIfMissingSubArrays ()
    {
        /**
         * Scenario:
         *
         * Given a fieldMapping-configuration for an existing table
         * Given that fieldMapping has no field-definition for the core
         * Given that fieldMapping has a field-definition for a non-existing extension
         * Given setTableName has been called with that tableName
         * When the method is called
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 1690953438
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690953438);

        $settings = $this->getSettings();
        FieldMappingUtility::getMapping($settings['fieldMapping'], 'sys_template');

    }


    /**
     * @test
     * @throws \Exception
     */
    public function getMappingReturnsMappingWithoutFieldsOfUninstalledExtension ()
    {
        /**
         * Scenario:
         *
         * Given a fieldMapping-configuration for an existing table
         * Given that fieldMapping has field-definitions for the core
         * Given that fieldMapping has field-definitions for non-existing extension
         * Given setTableName has been called with that tableName
         * When the method is called
         * Then an array is returned
         * Then this array contains all field-definitions for the core
         * Then this array does not contain the field-definitions for non-existing extension
         */

        $settings = $this->getSettings();
        $result = FieldMappingUtility::getMapping($settings['fieldMapping'], 'fe_users');

        $expected = [
            'uid' => [
                'key' => 'uid',
                'type' => 'int'
            ],
            'crdate' => [
                'key' => 'crdate',
                'type' => 'int'
            ],
            'username' => [
                'key' => 'username',
                'type' => 'string'
            ],
            'first_name' => [
                'key' => 'first_name',
                'type' => 'int'
            ],
            'last_name' => [
                'key' => 'last_name',
                'type' => 'string'
            ],
            'email' => [
                'key' => 'email',
                'type' => 'string'
            ],
        ];

        self::assertIsArray($result);
        self::assertEquals($expected, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getMappingReturnsMappingWithFieldsOfInstalledExtension ()
    {
        /**
         * Scenario:
         *
         * Given a fieldMapping-configuration for an existing table
         * Given that fieldMapping has field-definitions for the core
         * Given that fieldMapping has field-definitions for an installed extension
         * Given setTableName has been called with that tableName
         * When the method is called
         * Then an array is returned
         * Then this array contains all field-definitions for the core
         * Then this array does contain the field-definitions for the installed extension
         */

        $settings = $this->getSettings();
        $result = FieldMappingUtility::getMapping($settings['fieldMapping'], 'pages');

        $expected = [
            'uid' => [
                'key' => 'uid',
                'type' => 'int'
            ],
            'crdate' => [
                'key' => 'crdate',
                'type' => 'int'
            ],
            'title' => [
                'key' => 'title',
                'type' => 'string'
            ],
            'tx_coreextended_alternative_title' => [
                'key' => 'tx_coreextended_alternative_title',
                'type' => 'string'
            ],
        ];

        self::assertIsArray($result);
        self::assertEquals($expected, $result);
    }
    #==============================================================================

    /**
     * Returns TYPO3 settings
     *
     * @param string $which Which type of settings will be loaded
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings(string $which = ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS): array
    {
        return \Madj2k\CoreExtended\Utility\GeneralUtility::getTypoScriptConfiguration('soapapi', $which);
    }


    /**
     * TearDown
     */
    protected function teardown(): void
    {
        parent::tearDown();
    }


}
