<?php
namespace Madj2k\SoapApi\Soap;

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

use Madj2k\CoreExtended\Utility\GeneralUtility as Common;
use Madj2k\SoapApi\Data\DataHandler;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class Server
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_SoapApi
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Server implements ServerInterface
{

      /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager|null
     */
    protected ?PersistenceManager $persistenceManager = null;


    /**
     * @var \Madj2k\SoapApi\Data\DataHandler|null
     */
    protected ?DataHandler $dataHandler = null;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * @var array
     */
    protected array $settings = [];


    /**
     * Constructor
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @returns void
     */
    public function __construct()
    {
        $this->settings = $this->getSettings();

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\SoapApi\Data\DataHandler dataHandler */
        $this->dataHandler = $objectManager->get(DataHandler::class);

        // set default storagePids
        if ($this->settings['soapServer']['storagePids']) {
            $this->dataHandler->setStoragePids(['soapServer']['storagePids']);
        }
    }


    /**
     * Returns current version
     *
     * @return string
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Package\Exception
     */
    public function getVersion(): string
    {
        $version = ExtensionManagementUtility::getExtensionVersion('soap_api');
        if ($this->settings['soapServer']['version']) {
            $version = $this->settings['soapServer']['version'];
        }

        return $version;
    }


    /**
     * Returns current storagePids
     *
     * @return string a comma-separated string of integers
     */
    public function getStoragePids(): string
    {
        return $this->dataHandler->getStoragePids();
    }


    /**
     * Set current storagePids
     *
     * @param string $storagePids a comma-separated string of integers
     * @return void
     */
    public function setStoragePids(string $storagePids): void
    {
        $this->dataHandler->setStoragePids($storagePids);
    }


    /**
     * Returns all FE-users that have been updated since $timestamp
     * Alias of $this->findFeUsersByTimestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     * @deprecated since 05-10-2017
     */
    public function findFeUserByTimestamp(int $timestamp): array
    {
        trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated and will be removed soon', E_USER_DEPRECATED);
        return $this->findFeUsersByTimestamp($timestamp);
    }


    /**
     * Returns all FE-users that have been updated since $timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findFeUsersByTimestamp(int $timestamp): array
    {
        $this->dataHandler->setTableName('fe_users');
        $result = $this->dataHandler->findByTstamp($timestamp);

        $resultFinal = [];
        foreach ($result as $feUser) {
            if (
                (! $feUser['first_name'])
                && (! $feUser['last_name'])
            ) {

                // try to get additional data from shippingAddress-table
                try {
                    $this->dataHandler->setTableName('tx_feregister_domain_model_shippingaddress');
                    $shippingAddress = $this->dataHandler->findOneByFrontendUser(intval($feUser['uid']));
                    if (!$shippingAddress) {
                        continue;
                    }

                    $feUser['tx_rkwregistration_gender'] = $shippingAddress['gender'];
                    $feUser['first_name'] = $shippingAddress['first_name'];
                    $feUser['last_name'] = $shippingAddress['last_name'];
                    $feUser['address'] = $shippingAddress['address'];
                    $feUser['zip'] = $shippingAddress['zip'];
                    $feUser['city'] = $shippingAddress['city'];
                    $feUser['company'] = $shippingAddress['company'];

                } catch (\Exception $e) {
                    continue;
                }
            }

            $resultFinal[] = $feUser;
        }

        return $resultFinal;
    }


    /**
     * Returns a FE-users by uid
     *
     * @param int $uid
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findFeUserByUid(int $uid): array
    {
        $this->dataHandler->setTableName('fe_users');
        return $this->dataHandler->findByUid($uid);
    }


    /**
     * Returns all FE-users that have been updated since $timestamp
     * Alias of $this->findFeUserGroupsByTimestamp
     *
     * @param int $timestamp unix-format
     * @param int $serviceOnly deprecated, no functionality any more
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     * @deprecated since 05-10-2017
     */
    public function findFeUserGroupByTimestamp(int $timestamp, int $serviceOnly = 0): array
    {
        trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated and will be removed soon', E_USER_DEPRECATED);
        return $this->findFeUserGroupsByTimestamp($timestamp, $serviceOnly);
    }


    /**
     * Returns all FE-users that have been updated since $timestamp
     *
     * @param int $timestamp unix-format
     * @param int $serviceOnly deprecated, no functionality any more
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findFeUserGroupsByTimestamp(int $timestamp, int $serviceOnly = 0): array
    {
        $this->dataHandler->setTableName('fe_groups');
        return $this->dataHandler->findByTstamp($timestamp);
    }


    /**
     * Returns all new orders since $timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopFindOrdersByTimestamp(int $timestamp = 0): array
    {
        $this->dataHandler->setTableName('tx_rkwshop_domain_model_order');
        $orders = $this->dataHandler->findByTstamp($timestamp);

        // add shipping-address to each single order
        foreach ($orders as &$order) {

            if ($order['shipping_address']) {

                $this->dataHandler->setTableName('tx_feregister_domain_model_shippingaddress');
                $shippingAddress = $this->dataHandler->findByUid(intval($order['shipping_address']));

                if ($shippingAddress) {
                    $order = array_merge($order, $shippingAddress);
                }
                unset($order['shipping_address']);
            }
        }

        return $orders;
    }


    /**
     * Sets status-property for given order-uid
     *
     * @param int $orderUid
     * @param int $status allowed values: 0=new 90=exported 100=sent 200=closed
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopSetStatusForOrder(int $orderUid, int $status): bool
    {
        $data = [
            'status' => $status
        ];

        $this->dataHandler->setTableName('tx_rkwshop_domain_model_order');
        return (bool) $this->dataHandler->updateByUid($orderUid, $data);
    }


    /**
     * Sets deleted-property for given order-uid
     *
     * @param int $orderUid
     * @param int $deleted allowed values: 0=not deleted 1=deleted
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopSetDeletedForOrder(int $orderUid, int $deleted): bool
    {
        $data = [
            'deleted' => $deleted
        ];

        $this->dataHandler->setTableName('tx_rkwshop_domain_model_order');
        return (bool) $this->dataHandler->updateByUid($orderUid, $data);
    }


    /**
     * Returns all order-items for given order-uid
     *
     * @param int $orderUid
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopFindOrderItemsByOrder(int $orderUid): array
    {
        $this->dataHandler->setTableName('tx_rkwshop_domain_model_orderitem');
        return $this->dataHandler->findByExtOrder($orderUid);
    }


    /**
     * Sets status-property for given orderItem-uid
     *
     * @param int $orderItemUid
     * @param int $status allowed values: 0=new 90=exported 100=sent 200=closed
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopSetStatusForOrderItem(int $orderItemUid, int $status): bool
    {
        $data = [
            'status' => $status
        ];

        $this->dataHandler->setTableName('tx_rkwshop_domain_model_orderitem');
        return (bool) $this->dataHandler->updateByUid($orderItemUid, $data);
    }


    /**
     * Returns all products
     *
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopFindAllProducts(): array
    {
        $this->dataHandler->setTableName('tx_rkwshop_domain_model_product');
        $products =  $this->dataHandler->findAll();

        /** @todo deprecated, for backwards compatibility */
        foreach ($products as &$product) {
            $product['stock'] = 5000;
        }

        return $products;
    }


    /**
     * Sets externalOrders-property for given product-uid
     *
     * @param int $productUid
     * @param int $orderedExternal
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopSetOrderedExternalForProduct(int $productUid, int $orderedExternal): bool
    {
        $this->dataHandler->setTableName('tx_rkwshop_domain_model_product');
        return (bool) $this->dataHandler->updateByUid($productUid, ['ordered_external' => $orderedExternal]);

    }


    /**
     * Finds all stocks for given product-uid
     *
     * @param int $productUid
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopFindStocksByProduct(int $productUid): array
    {
        $this->dataHandler->setTableName('tx_rkwshop_domain_model_stock');
        return $this->dataHandler->findByProduct($productUid);
    }


    /**
     * Adds stock for given product-uid
     *
     * @param int $productUid
     * @param int $amount
     * @param string $comment
     * @param int $deliveryStart unix-format
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopAddStockForProduct(int $productUid, int $amount, string $comment, int $deliveryStart = 0): bool
    {
        $data = [
            'product' => $productUid,
            'amount' => $amount,
            'comment' => $comment,
            'delivery_start' => $deliveryStart
        ];

        $this->dataHandler->setTableName('tx_rkwshop_domain_model_stock');
        return (bool) $this->dataHandler->insert($data);
    }


    /**
     * Returns all existing events by timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwEventsFindEventsByTimestamp(int $timestamp): array
    {

        $this->dataHandler->setTableName('tx_rkwevents_domain_model_event');
        return $this->dataHandler->findByTstamp($timestamp);
    }


    /**
     * Returns all existing eventPlaces by timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwEventsFindEventPlacesByTimestamp(int $timestamp): array
    {
        $this->dataHandler->setTableName('tx_rkwevents_domain_model_eventplace');
        return $this->dataHandler->findByTstamp($timestamp);
    }


    /**
     * Returns all existing eventReservations by timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwEventsFindEventReservationsByTimestamp(int $timestamp): array
    {
        $this->dataHandler->setTableName('tx_rkwevents_domain_model_eventreservation');
        return $this->dataHandler->findByTstamp($timestamp);
    }


    /**
     * Returns all existing eventReservationAddPersons by timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwEventsFindEventReservationAddPersonsByTimestamp(int $timestamp): array
    {
        $this->dataHandler->setTableName('tx_rkwevents_domain_model_eventreservationaddperson');
        return $this->dataHandler->findByTstamp($timestamp);
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
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
        return Common::getTypoScriptConfiguration('rkwsoap', $which);
    }

}

