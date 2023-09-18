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

/**
 * Interface Server
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_SoapApi
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface ServerInterface
{

    /**
     * Returns current version
     *
     * @return string
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Package\Exception
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function getVersion(): string;


    /**
     * Returns current storagePids
     *
     * @return string a comma-separated string of integers
     */
    public function getStoragePids(): string;


    /**
     * Set current storagePids
     *
     * @param string $storagePids a comma-separated string of integers
     * @return void
     */
    public function setStoragePids(string $storagePids): void;


     /**
     * Returns all FE-users that have been updated since $timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findFeUsersByTimestamp(int $timestamp): array;


    /**
     * Returns a FE-users by uid
     *
     * @param int $uid
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findFeUserByUid(int $uid): array;


    /**
     * Update for given frontendUser
     *
     * @param int $uid
     * @param string $dataString
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function updateFeUserByUid(int $uid, string $dataString): bool;


    /**
     * Returns all FE-users that have been updated since $timestamp
     *
     * @param int $timestamp unix-format
     * @param int $serviceOnly deprecated, no functionality any more
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function findFeUserGroupsByTimestamp(int $timestamp, int $serviceOnly = 0): array;


    /**
     * Returns all new orders since $timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopFindOrdersByTimestamp(int $timestamp = 0): array;


    /**
     * Sets status-property for given order-uid
     *
     * @param int $orderUid
     * @param int $status allowed values: 0=new 90=exported 100=sent 200=closed
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopSetStatusForOrder(int $orderUid, int $status): bool;


    /**
     * Sets deleted-property for given order-uid
     *
     * @param int $orderUid
     * @param int $deleted allowed values: 0=not deleted 1=deleted
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopSetDeletedForOrder(int $orderUid, int $deleted): bool;


    /**
     * Returns all order-items for given order-uid
     *
     * @param int $orderUid
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopFindOrderItemsByOrder(int $orderUid): array;


    /**
     * Sets status-property for given orderItem-uid
     *
     * @param int $orderItemUid
     * @param int $status allowed values: 0=new 90=exported 100=sent 200=closed
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopSetStatusForOrderItem(int $orderItemUid, int $status): bool;


    /**
     * Returns all products
     *
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopFindAllProducts(): array;


    /**
     * Sets externalOrders-property for given product-uid
     *
     * @param int $productUid
     * @param int $orderedExternal
     * @return bool
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopSetOrderedExternalForProduct(int $productUid, int $orderedExternal): bool;


    /**
     * Finds all stocks for given product-uid
     *
     * @param int $productUid
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwShopFindStocksByProduct(int $productUid): array;


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
    public function rkwShopAddStockForProduct(int $productUid, int $amount, string $comment, int $deliveryStart = 0): bool;


    /**
     * Returns all existing events by timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwEventsFindEventsByTimestamp(int $timestamp): array;


    /**
     * Returns all existing eventPlaces by timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwEventsFindEventPlacesByTimestamp(int $timestamp): array;


    /**
     * Returns all existing eventReservations by timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwEventsFindEventReservationsByTimestamp(int $timestamp): array;


    /**
     * Returns all existing eventReservationAddPersons by timestamp
     *
     * @param int $timestamp unix-format
     * @return array
     * @throws \Madj2k\SoapApi\Exception
     */
    public function rkwEventsFindEventReservationAddPersonsByTimestamp(int $timestamp): array;

}

