<?php
namespace Madj2k\SoapApi\Data;

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

use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\CoreExtended\Utility\QueryUtility;
use Madj2k\SoapApi\Utility\FieldMappingUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class DataHandler
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_SoapApi
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DataHandler
{

    /**
     * @const
     */
    const TYPE_STRING = 'string';


    /**
     * @const
     */
    const TYPE_INT = 'int';


    /**
     * @const
     */
    const TYPE_FLOAT = 'float';


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ObjectManager $objectManager;


    /**
     * @var \TYPO3\CMS\Core\Database\Query\QueryBuilder|null
     */
    protected ?QueryBuilder $queryBuilder = null;


    /**
     * @var string
     */
    protected string $tableName = '';


    /**
     * @var array
     */
    protected array $settings = [];


    /**
     * @var string
     */
    protected string $storagePids = '';


    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManager $objectManager): void
    {
        $this->objectManager = $objectManager;
    }


    /**
     * Construct
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function __construct()
    {
        $this->settings = $this->getSettings();
    }


    /**
     * Gets the tableName
     *
     * @return string
     */
    public function getTableName (): string
    {
        return $this->tableName;
    }


    /**
     * Sets the tableName
     *
     * @param string $tableName
     * @return void
     */
    public function setTableName (string $tableName): void
    {
        $this->tableName = $tableName;
        $this->queryBuilder = null;
    }


    /**
     * Gets the storagePids
     *
     * @return string
     */
    public function getStoragePids (): string
    {
        return $this->storagePids;
    }


    /**
     * Sets the storagePids
     *
     * @param string $storagePids
     * @return void
     */
    public function setStoragePids (string $storagePids): void
    {
        $this->storagePids = $storagePids;
    }


    /**
     * Find data by identifier
     * Alias of findByUid
     *
     * @param int $uid
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findByIdentifier(int $uid = 0): array
    {
        return $this->findByUid($uid);
    }


    /**
     * Find data by uid
     *
     * @param int $uid
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findByUid(int $uid = 0): array
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();

        // default
        $queryBuilder->where(
            $queryBuilder->expr()->eq('uid',
                $queryBuilder->createNamedParameter(
                    $uid, Connection::PARAM_INT
                )
            )
        );

        $queryBuilder->setMaxResults(1);
        $result = $this->_find($queryBuilder);
        return $result[0];
    }


    /**
     * Find all data by timestamp
     *
     * @param int $timestamp
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findByTstamp(int $timestamp = 0): array
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();

        // default
        $queryBuilder->where(
            $queryBuilder->expr()->gte('tstamp',
                $queryBuilder->createNamedParameter(
                    $timestamp, Connection::PARAM_INT
                )
            )
        );

        $queryBuilder->orderBy('tstamp', 'ASC');
        return $this->_find($queryBuilder);
    }


    /**
     * Find all data by pid and timestamp
     *
     * @param int $parent
     * @param int $timestamp
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findByParentAndTstamp(int $parent = 0, int $timestamp = 0): array
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();

        // default
        $queryBuilder->where(
            $queryBuilder->expr()->gte('tstamp',
                $queryBuilder->createNamedParameter(
                    $timestamp, Connection::PARAM_INT
                )
            )
        )->andWhere(
            $queryBuilder->expr()->eq('parent',
                $queryBuilder->createNamedParameter(
                    $parent, Connection::PARAM_INT
                )
            )
        );

        $queryBuilder->orderBy('tstamp', 'ASC');
        return $this->_find($queryBuilder);
    }


    /**
     * Find all data
     *
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findAll(): array
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();
        return $this->_find($queryBuilder);
    }


    /**
     * General method for finding data
     *
     * @param \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function _find(QueryBuilder $queryBuilder): array
    {
        $propertyMapping = FieldMappingUtility::getMapping($this->settings['fieldMapping'], $this->tableName);

        $queryBuilder
            ->select(...array_keys($propertyMapping))
            ->from($this->tableName);

        // check for storagePid
        if ($this->storagePids) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter(
                        GeneralUtility::intExplode(',', $this->storagePids, true),
                        Connection::PARAM_INT_ARRAY
                    )
                )
            );
        }

        $result = [];
        $statement = $queryBuilder->execute();

        $this->getLogger()->log(
            LogLevel::INFO,
            sprintf(
                'SQL-Query: %s',
                QueryUtility::getFullSql($queryBuilder)
            )
        );

        while ($row = $statement->fetchAssociative()) {

            // do typeCasting and key-mapping
            $tempRow = [];
            foreach ($row as $key => $value) {
                $tempRow[$propertyMapping[$key]['key']] = $this->typeCast($propertyMapping[$key]['type'], $value);
            }
            $result[] = $tempRow;
        }

        $this->queryBuilder = null;
        return $result;
    }


    /**
     * Update data by uid
     *
     * @param int $uid
     * @param array $keyValuePairs
     * @return int
     * @throws \Madj2k\SoapApi\Exception
     */
    public function updateByUid(int $uid, array $keyValuePairs): int
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();

        // default
        $queryBuilder->where(
            $queryBuilder->expr()->eq('uid',
                $queryBuilder->createNamedParameter(
                    $uid, Connection::PARAM_INT
                )
            )
        );

        return $this->_update($queryBuilder, $keyValuePairs);
    }


    /**
     * General method for updating data
     *
     * @param \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder
     * @param array $keyValuePairs
     * @return int
     * @throws \Madj2k\SoapApi\Exception
     */
    protected function _update(QueryBuilder $queryBuilder, array $keyValuePairs): int
    {
        $propertyMapping = FieldMappingUtility::getMapping($this->settings['fieldMapping'], $this->tableName);

        // do typeCasting and reverse field-mapping
        $matched = false;
        foreach ($propertyMapping as $field => $fieldMapping) {
            $key = $fieldMapping['key'];
            $type = $fieldMapping['type'];
            if (isset($keyValuePairs[$key])) {
                $queryBuilder->set($field, $this->typeCast($type, $keyValuePairs[$key]));
                $matched = true;
            }
        }

        if (! $matched) {
            throw new \Madj2k\SoapApi\Exception(
                'No valid key-value array for update given' . print_r($keyValuePairs, true),
                1690901204
            );
        }

        $result = $queryBuilder
            ->update($this->tableName)
            ->execute();

        $this->getLogger()->log(
            LogLevel::INFO,
            sprintf(
                'SQL-Query: %s',
                QueryUtility::getFullSql($queryBuilder)
            )
        );

        $this->queryBuilder = null;
        return $result;
    }


    /**
     * Insert data
     *
     * @param array $keyValuePairs
     * @return int
     * @throws \Madj2k\SoapApi\Exception
     */
    public function insert(array $keyValuePairs): int
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();

        return $this->_insert($queryBuilder, $keyValuePairs);
    }


    /**
     * General method for inserting data
     *
     * @param \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder
     * @param array $keyValuePairs
     * @return int
     * @throws \Madj2k\SoapApi\Exception
     */
    protected function _insert(QueryBuilder $queryBuilder, array $keyValuePairs): int
    {
        $propertyMapping = FieldMappingUtility::getMapping($this->settings['fieldMapping'], $this->tableName);

        // do typeCasting and reverse field-mapping
        $mappedKeyValuePairs = [];
        foreach ($propertyMapping as $field => $fieldMapping) {
            $key = $fieldMapping['key'];
            $type = $fieldMapping['type'];
            if (isset($keyValuePairs[$key])) {
                $mappedKeyValuePairs[$field] = $this->typeCast($type, $keyValuePairs[$key]);
            }
        }

        if (! $mappedKeyValuePairs) {
            throw new \Madj2k\SoapApi\Exception(
                'No valid key-value array for update given',
                1690901205
            );
        }

        $result = $queryBuilder
            ->insert($this->tableName)
            ->values($mappedKeyValuePairs)
            ->execute();

        $this->getLogger()->log(
            LogLevel::INFO,
            sprintf(
                'SQL-Query: %s',
                QueryUtility::getFullSql($queryBuilder)
            )
        );

        $this->queryBuilder = null;
        return $result;
    }


    /**
     * Dispatches magic methods (findBy[Property]())
     *
     * @param string $methodName The name of the magic method
     * @param array $arguments The arguments of the magic method
     * @return mixed
     * @throws \Madj2k\SoapApi\Exception
     */
    public function __call(string $methodName, array $arguments)
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->getQueryBuilder();

        if (strpos($methodName, 'findBy') === 0 && strlen($methodName) > 7) {
            $propertyName = Generalutility::underscore(substr($methodName, 6));
            $queryBuilder->where(
                $queryBuilder->expr()->eq($propertyName, $queryBuilder->createNamedParameter($arguments[0]))
            );
            return $this->_find($queryBuilder);
        }
        if (strpos($methodName, 'updateBy') === 0 && strlen($methodName) > 9) {
            $propertyName = Generalutility::underscore(substr($methodName, 8));
            $queryBuilder->where(
                $queryBuilder->expr()->eq($propertyName, $queryBuilder->createNamedParameter($arguments[0]))
            );
            return $this->_update($queryBuilder, $arguments[1]);
        }
        if (strpos($methodName, 'findOneBy') === 0 && strlen($methodName) > 10) {
            $propertyName = Generalutility::underscore(substr($methodName, 9));
            $queryBuilder->where(
                $queryBuilder->expr()->eq($propertyName, $queryBuilder->createNamedParameter($arguments[0]))
            );
            $queryBuilder->setMaxResults(1);

            $result = $this->_find($queryBuilder);
            return $result[0];
        }

        throw new \Madj2k\SoapApi\Exception(
            sprintf(
                'The method "%s" is not supported',
                $methodName
            ),
            1690524575
        );
    }


    /**
     * Get queryBuilder for current table
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     * @throws \Madj2k\SoapApi\Exception
     */
    public function getQueryBuilder(): QueryBuilder
    {
        if (! $this->tableName) {
            throw new \Madj2k\SoapApi\Exception(
                'No tableName set',
                1690524569
            );
        }

        if (! $this->queryBuilder instanceof \TYPO3\CMS\Core\Database\Query\QueryBuilder) {

            /** @var \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool */
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $this->queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
            $this->queryBuilder->getRestrictions()->removeAll();
        }

        return $this->queryBuilder;
    }


    /**
     * Do typecast based on given type
     *
     * @param string $type
     * @param $value
     * @return float|int|string
     * @throws \Madj2k\SoapApi\Exception
     */
    public function typeCast(string $type, $value)
    {
        switch ($type) {
            case self::TYPE_INT:
                $value = intval($value);
                break;
            case self::TYPE_FLOAT:
                $value = floatval($value);
                break;
            case self::TYPE_STRING:
                $value = (string)$value;
                break;
            default:
                throw new \Madj2k\SoapApi\Exception(
                    sprintf('Invalid data type "%s"', $type),
                    1690524571
                );
        }

        return $value;
    }


    /**
     * Returns TYPO3 settings
     *
     * @param string $which Which type of settings will be loaded
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings(string $which = ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS): array
    {
        return GeneralUtility::getTypoScriptConfiguration('soapapi', $which);
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }
}

