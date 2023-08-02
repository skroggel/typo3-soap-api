<?php
namespace Madj2k\SoapApi\Tests\Integration\Data;

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


use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Madj2k\CoreExtended\Utility\FrontendSimulatorUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Domain\Model\OptIn;
use Madj2k\FeRegister\Domain\Repository\BackendUserRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\OptInRepository;
use Madj2k\FeRegister\Domain\Repository\ConsentRepository;
use Madj2k\FeRegister\Registration\FrontendUserRegistration;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\SoapApi\Data\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * DataHandlerTest
 *
 * @author Steffen Krogel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_SoapApi
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DataHandlerTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/DataHandlerTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/core_extended',
    ];


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'filemetadata',
        'seo'
    ];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|null
     */
    private ?ObjectManager $objectManager = null;


    /**
     * @var \Madj2k\SoapApi\Data\DataHandler|null
     */
    private ?DataHandler $fixture = null;


    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Global.xml');

        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:core_extended/Configuration/TypoScript/setup.typoscript',
                'EXT:core_extended/Configuration/TypoScript/constants.typoscript',
                'EXT:soap_api/Configuration/TypoScript/setup.typoscript',
                'EXT:soap_api/Configuration/TypoScript/constants.typoscript',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ],
            ['example.com' => self::FIXTURE_PATH .  '/Frontend/Configuration/config.yaml']
        );

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** \Madj2k\SoapApi\Data\DataHandler $fixture */
        $this->fixture = $this->objectManager->get(DataHandler::class);
    }


    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function findByUidReturnsSingleData ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * When the method is called with a unique-id
         * Then an array is returned
         * Then this array contains no rows but directly the expected fields
         * Then the uid-key has the value of the given unique-id
         * Then this array only contains the defined fields
         * Then a type-cast is done
         * Then a field-mapping is done
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->findByUid(11);

        $expectedKeys = [
            'uid', 'crdate', 'username_as_key', 'first_name', 'last_name', 'email', 'zip'
        ];

        self::assertIsArray($result);
        self::assertCount(7, $result);
        self::assertEquals(11, $result['uid']);

        self::assertEquals($expectedKeys, array_keys($result));

        self::assertEquals(1, $result['first_name']);
        self::assertEquals('lauterlein@spd.de', $result['username_as_key']);
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function findByTstampReturnsOnlyMatchingData ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * When the method is called with a tstamp-parameter
         * Then an array is returned
         * Then this array contains only the rows that match the tstamp
         * Then the array is sorted by tstamp ASC
         * Then every row only contains the defined fields
         * Then a type-cast is done
         * Then a field-mapping is done
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->findByTstamp(10000);

        $expectedKeys = [
            'uid', 'crdate', 'username_as_key', 'first_name', 'last_name', 'email', 'zip'
        ];

        self::assertIsArray($result);
        self::assertCount(2, $result);

        self::assertEquals(12, $result[0]['uid']);
        self::assertEquals(11, $result[1]['uid']);

        self::assertEquals($expectedKeys, array_keys($result[0]));
        self::assertEquals($expectedKeys, array_keys($result[1]));

        self::assertEquals(0, $result[0]['first_name']);
        self::assertEquals(1, $result[1]['first_name']);

        self::assertEquals('lindnerchen@fdp.de', $result[0]['username_as_key']);
        self::assertEquals('lauterlein@spd.de', $result[1]['username_as_key']);

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function findAllReturnsAllData ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * When the method is called with no tstamp-parameter
         * Then an array is returned
         * Then this array contains all rows
         * Then every row only contains the defined fields
         * Then a type-cast is done
         * Then a field-mapping is done
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->findAll();

        $expectedKeys = [
            'uid', 'crdate', 'username_as_key', 'first_name', 'last_name', 'email', 'zip'
        ];

        self::assertIsArray($result);
        self::assertCount(3, $result);

        self::assertEquals($expectedKeys, array_keys($result[0]));
        self::assertEquals($expectedKeys, array_keys($result[1]));
        self::assertEquals($expectedKeys, array_keys($result[2]));

        self::assertEquals(0, $result[0]['first_name']);
        self::assertEquals(1, $result[1]['first_name']);
        self::assertEquals(0, $result[2]['first_name']);

        self::assertEquals('merkelchen@cdu.de', $result[0]['username_as_key']);
        self::assertEquals('lauterlein@spd.de', $result[1]['username_as_key']);
        self::assertEquals('lindnerchen@fdp.de', $result[2]['username_as_key']);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function findAllReturnsDataFromStoragePids ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * Given setStoragePid is called before with a pid-string
         * When the method is called with no tstamp-parameter
         * Then an array is returned
         * Then this array contains only the rows that match the storagePids
         * Then every row only contains the defined fields
         * Then a type-cast is done
         * Then a field-mapping is done
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $this->fixture->setTableName('fe_users');
        $this->fixture->setStoragePids('10,12');
        $result = $this->fixture->findAll();

        $expectedKeys = [
            'uid', 'crdate', 'username_as_key', 'first_name', 'last_name', 'email', 'zip'
        ];

        self::assertIsArray($result);
        self::assertCount(2, $result);

        self::assertEquals(10, $result[0]['uid']);
        self::assertEquals(12, $result[1]['uid']);

        self::assertEquals($expectedKeys, array_keys($result[0]));
        self::assertEquals($expectedKeys, array_keys($result[1]));

        self::assertEquals(0, $result[0]['first_name']);
        self::assertEquals(0, $result[1]['first_name']);

        self::assertEquals('merkelchen@cdu.de', $result[0]['username_as_key']);
        self::assertEquals('lindnerchen@fdp.de', $result[1]['username_as_key']);

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function updateByUidThrowsExceptionIfNoValidKeyValueArray ()
    {
        /**
         * Scenario:
         *
         * Given setTableName has been called before
         * Given a key-value-array with keys that have no match in the configuration
         * Given a persisted dataset with an unique id
         * When the method is called with the unique uid and that key value-array
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 1690901204
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690901204);

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $update = [
            'non_existing_key' => 'my value',
        ];

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->updateByUid(10, $update);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function updateByUidUpdatesData ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * Given setTableName has been called before
         * Given a key-value-array with keys that have a match in the configuration
         * Given a persisted dataset with an unique id
         * When the method is called with the unique uid and that key value-array
         * Then an integer is returned
         * Then the integer is one
         * Then the database-row with the given uid is updated
         * Then a reverse field-mapping is done
         * Then a typeCasting is done
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $update = [
            'username_as_key' => 'merzilein@cdu.de',
            'email' => 'friedrich.merz@cdu.de',
            'first_name' => '1Friedrich',
            'last_name' => 'Merz',
        ];

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->updateByUid(10, $update);

        $expected = $this->fixture->findByUid(10);

        self::assertIsInt($result);
        self::assertEquals(1, $result);

        self::assertEquals($update['username_as_key'], $expected['username_as_key']);
        self::assertEquals($update['email'], $expected['email']);
        self::assertEquals(1, $expected['first_name']);
        self::assertEquals($update['last_name'], $expected['last_name']);

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function insertThrowsExceptionIfNoValidKeyValueArray ()
    {
        /**
         * Scenario:
         *
         * Given setTableName has been called before
         * Given a key-value-array with keys that have no match in the configuration
         * When the method is called with that key value-array
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 1690901205
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690901205);

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $insert = [
            'non_existing_key' => 'my value',
        ];

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->insert($insert);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function insertInsertsData ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * Given setTableName has been called before
         * Given a key-value-array with keys that have a match in the configuration
         * When the method is called with that key value-array
         * Then an integer is returned
         * Then the integer is one
         * Then the database-row with the given data is inserted
         * Then a reverse field-mapping is done
         * Then a typeCasting is done
         */

        $insert = [
            'uid' => 5000,
            'username_as_key' => 'merzilein@cdu.de',
            'email' => 'friedrich.merz@cdu.de',
            'first_name' => '1Friedrich',
            'last_name' => 'Merz',
        ];

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->insert($insert);

        $expected = $this->fixture->findByUid(5000);

        self::assertIsInt($result);
        self::assertEquals(1, $result);

        self::assertEquals($insert['username_as_key'], $expected['username_as_key']);
        self::assertEquals($insert['email'], $expected['email']);
        self::assertEquals(1, $expected['first_name']);
        self::assertEquals($insert['last_name'], $expected['last_name']);

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function magicFindByReturnsOnlyMatchingData ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * When the method is called with a string
         * Then an array is returned
         * Then this array contains only the rows that match the string
         * Then every row only contains the defined fields
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->findByZip('10969');

        $expectedKeys = [
            'uid', 'crdate', 'username_as_key', 'first_name', 'last_name', 'email', 'zip'
        ];

        self::assertIsArray($result);
        self::assertCount(2, $result);

        self::assertEquals('10969', $result[0]['zip']);
        self::assertEquals('10969', $result[1]['zip']);

        self::assertEquals($expectedKeys, array_keys($result[0]));
        self::assertEquals($expectedKeys, array_keys($result[1]));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function magicFindByWithIntegerReturnsOnlyMatchingData ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * When the method is called with an integer
         * Then an array is returned
         * Then this array contains only the rows that match the integer
         * Then every row only contains the defined fields
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->findByDisable(0);

        $expectedKeys = [
            'uid', 'crdate', 'username_as_key', 'first_name', 'last_name', 'email', 'zip'
        ];

        self::assertIsArray($result);
        self::assertCount(2, $result);

        self::assertEquals(0, $result[0]['disable']);
        self::assertEquals(0, $result[1]['disable']);
        self::assertEquals(11, $result[0]['uid']);
        self::assertEquals(12, $result[1]['uid']);

        self::assertEquals($expectedKeys, array_keys($result[0]));
        self::assertEquals($expectedKeys, array_keys($result[1]));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function magicFindOneReturnsOnlyOneMatchingData ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * When the method is called with a string
         * Then an array is returned
         * Then this array contains no rows but directly the expected fields
         * Then this array contains only one database-row that matches the string
         * Then this array only contains the defined fields
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->findOneByLastName('Lauterbach');

        $expectedKeys = [
            'uid', 'crdate', 'username_as_key', 'first_name', 'last_name', 'email', 'zip'
        ];

        self::assertIsArray($result);
        self::assertCount(7, $result);

        self::assertEquals('Lauterbach', $result['last_name']);
        self::assertEquals($expectedKeys, array_keys($result));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function magicUpdateByUpdatesMatchingData ()
    {
        /**
         * Scenario:
         *
         * Given setTableName been called before
         * When the method is called with a key-value-array
         * Then an integer is returned
         * Then the integer has the value two
         * The two matching datasets have been updated
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->updateByZip('10969', ['zip' => '0815']);

        self::assertIsInt($result);
        self::assertEquals(2, $result);

        $result = $this->fixture->findByZip('0815');
        self::assertCount(2, $result);

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function getQueryBuilderThrowsExceptionIfNoTableNameSet ()
    {
        /**
         * Scenario:
         *
         * Given setTableName has not been called before
         * When the method is called
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 1690524569
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690524569);

        $this->fixture->getQueryBuilder();
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getQueryBuilderReturnsQueryBuilderForSetTableWithNoRestrictionsSet ()
    {
        /**
         * Scenario:
         *
         * Given setTableName has been called before
         * When the method is called
         * Then an instance of \TYPO3\CMS\Core\Database\Query\QueryBuilder is returned
         * Then the instance has no default restrictions
         * Then the instance is set to the given table
         */

        $this->fixture->setTableName('fe_users');
        $result = $this->fixture->getQueryBuilder();

        // test query
        $result->select('*')
            ->from('fe_users');

        self::assertInstanceOf(\TYPO3\CMS\Core\Database\Query\QueryBuilder::class, $result);
        self::assertEquals('SELECT * FROM `fe_users`', $result->getSQL());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getQueryBuilderReturnsSameObject ()
    {
        /**
         * Scenario:
         *
         * Given setTableName has been called before
         * Given the method has been called before
         * When the method is called
         * Then an instance of \TYPO3\CMS\Core\Database\Query\QueryBuilder is returned
         * Then this is the same instance that has been returned on the first call
         */

        $this->fixture->setTableName('fe_users');
        $resultFirst = $this->fixture->getQueryBuilder();
        $resultSecond = $this->fixture->getQueryBuilder();

        self::assertSame($resultFirst, $resultSecond);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getQueryBuilderReturnsDifferentObject ()
    {
        /**
         * Scenario:
         *
         * Given setTableName has been called before
         * Given the method has been called before
         * Given setTableName has been called before again
         * When the method is called
         * Then an instance of \TYPO3\CMS\Core\Database\Query\QueryBuilder is returned
         * Then this is not the same instance that has been returned on the first call
         */

        $this->fixture->setTableName('fe_users');
        $resultFirst = $this->fixture->getQueryBuilder();

        $this->fixture->setTableName('fe_users');
        $resultSecond = $this->fixture->getQueryBuilder();

        self::assertNotSame($resultFirst, $resultSecond);
    }
    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function typeCastThrowsExceptionIfInvalidType ()
    {
        /**
         * Scenario:
         *
         * Given a string
         * When the method is called with an invalid type-parametet
         * Then the exception is an instance of \Madj2k\SoapApi\Exception
         * Then the exception has the code 1690524571
         */

        static::expectException(\Madj2k\SoapApi\Exception::class);
        static::expectExceptionCode(1690524571);

        $this->fixture->typeCast('invalid', 'test');

    }


    /**
     * @test
     * @throws \Exception
     */
    public function typeCastReturnsInteger ()
    {
        /**
         * Scenario:
         *
         * Given a string
         * When the method is called with 'int' as type-parameter
         * Then an integer is returned
         */

        $result = $this->fixture->typeCast(DataHandler::TYPE_INT, 'test');
        self::assertIsInt($result);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function typeCastReturnsFloat ()
    {
        /**
         * Scenario:
         *
         * Given an integer value
         * When the method is called with 'float' as type-parameter
         * Then a float is returned
         */

        $result = $this->fixture->typeCast(DataHandler::TYPE_FLOAT, 48);
        self::assertIsFloat($result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function typeCastReturnsString()
    {
        /**
         * Scenario:
         *
         * Given an integer value
         * When the method is called with 'string' as type-parameter
         * Then a string is returned
         */

        $result = $this->fixture->typeCast(DataHandler::TYPE_STRING, 48);
        self::assertIsString($result);
    }

    #==============================================================================

    /**
     * TearDown
     */
    protected function teardown(): void
    {
        FrontendSimulatorUtility::resetFrontendEnvironment();

        parent::tearDown();
    }

}
