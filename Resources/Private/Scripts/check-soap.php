<?php
ini_set('display_errors', 'On');

// $client = new \SoapClient("http://soap.rkw.codes/index.php?type=1690546816&wsdl=1&no_varnish=1",
$client = new \SoapClient("http://rkw-kompetenzzentrum.rkw.local/?type=1690546816&wsdl=1",
// $client = new \SoapClient("https://soap.rkw-kompetenzzentrum.de/index.php?type=1690546816&wsdl=1&no_varnish=1",

array(
    'trace'      => 1,
    'exceptions' => 1,
    'cache_wsdl' => WSDL_CACHE_NONE,
//    'login' => '',
//    'password' => '',
 //   'proxy_host' => 'proxy',
  //  'proxy_port' => '80'
)
);


# ----- Default -----
# $client->getStoragePids();
# $client->setStoragePids('1');
# $result = $client->getVersion();
# ----- FeUsers -----
 $result = $client->findFeUsersByTimestamp(12);
# ----- FeUserGroups ------
# $result = $client->findFeUserGroupsByTimestamp(12, 0);
# ------ Order ------
# $result = $client->rkwShopFindOrdersByTimestamp(1);
# $result = $client->rkwShopSetDeletedForOrder(1,1);
# $result = $client->rkwShopSetStatusForOrder(1,200);
# ----- OrderItems -----
# $result = $client->rkwShopFindOrderItemsByOrder(1);
# $result = $client->rkwShopSetStatusForOrderItem(1,200); // neues Feld: status
# ----- Products -----
# $result = $client->rkwShopFindAllProducts();
# $result = $client->rkwShopSetOrderedExternalForProduct(1,1000);
# ----- Stocks -----
# $result = $client->rkwShopFindStocksByProduct(1308);
# $result = $client->rkwShopAddStockForProduct(1308, 999, 'test', 12354);
# ----- Events -----
# $result = $client->rkwEventsfindEventsByTimestamp(1507230588);
# $result = $client->rkwEventsfindEventPlacesByTimestamp(1507230588);
# $result = $client->rkwEventsFindEventReservationsAddPersonByTimestamp(1);

echo "<pre>";

var_dump($result);

