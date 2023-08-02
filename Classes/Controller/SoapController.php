<?php
namespace Madj2k\SoapApi\Controller;

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

use Madj2k\CoreExtended\Utility\ClientUtility;
use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\SoapApi\Soap\Server;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;

/**
 * Class SoapController
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_SoapApi
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SoapController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * action WSDL
     *
     * @return void
     */
    public function wsdlAction(): void
    {

        // check if an url is set
        if ($this->settings['soapServer']['url']) {

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf('Successful WSDL request from IP %s.', ClientUtility::getIp())
            );

        } else {

            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 300');
            $this->getLogger()->log(LogLevel::ERROR, 'Service unavailable.');
            exit();
        }
    }


    /**
     * action soap
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
     */
    public function soapAction(): void
    {
        // kill TYPO3 output buffer
        while (ob_end_clean());

        // include authentification for PHP-CGI
        list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(
            ':',
            base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6))
        );

        // only WSDL wanted?
        if (isset($_GET['wsdl'])) {
            $this->forward('wsdl');
            exit();
        }

        // get remote addr
        $remoteAddr = ClientUtility::getIp();

        // check login
        if (
            (
                ($this->settings['soapServer']['disableSecurityChecks'] == 0)
                || (strtolower(GeneralUtility::getApplicationContext()->__toString()) == 'production')
            )
            && (! strtolower(GeneralUtility::getApplicationContext()->__toString()) == 'development')
        ){
            if (
                ($_SERVER['PHP_AUTH_USER'] != $this->settings['soapServer']['username'])
                || ($_SERVER['PHP_AUTH_PW'] != $this->settings['soapServer']['password'])
                || (
                    ($allowedIps = GeneralUtility::trimExplode(',', $this->settings['soapServer']['allowedRemoteIpList'], true))
                    && (!in_array($remoteAddr, $allowedIps))
                )
            ) {

                header('WWW-Authenticate: Basic realm="Checking Authentication"');
                header('HTTP/1.0 401 Unauthorized');
                $this->getLogger()->log(
                    LogLevel::WARNING,
                    sprintf('Login failed for user "%s" from IP %s.', $_SERVER['PHP_AUTH_USER'], $remoteAddr)
                );
                exit;
            }
        }

        // check if an url is set
        if ($this->settings['soapServer']['url']) {

            try {

                $options = array(
                    'uri'      => $this->settings['soapServer']['url'] . '/?type=1445105145',
                    'location' => $this->settings['soapServer']['url'],
                    'style'    => SOAP_RPC,
                    'use'      => SOAP_LITERAL,
                );

                $server = new \SoapServer(null, $options);
                $server->setClass(Server::class);

                // By MF: In relation of the pageIdentifier getter & setter:
                // https://stackoverflow.com/questions/33076844/how-to-set-member-variables-on-php-soap-class
                $server->setPersistence(SOAP_PERSISTENCE_SESSION);

                $server->handle();

                $this->getLogger()->log(
                    LogLevel::INFO,
                    sprintf('Successful SOAP call from IP %s. Request: %s',
                        $remoteAddr,
                        str_replace(array("\n", "\r"), '', file_get_contents("php://input"))
                    )
                );
                exit();

            } catch (\Exception $e) {
                $this->getLogger()->log(
                    LogLevel::ERROR,
                    sprintf(
                        'An error occurred. Message: %s. Request: %s',
                        str_replace(array("\n", "\r"), '', $e->getMessage()),
                        str_replace(array("\n", "\r"), '', file_get_contents("php://input"))
                    )
                );
                header('HTTP/1.1 500 Internal Server Error');
                exit();

            }
        }

        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 300');
        $this->getLogger()->log(LogLevel::ERROR, 'Service unavailable.');
        exit();
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
    {

        if (!$this->logger instanceof \TYPO3\CMS\Core\Log\Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }
}
