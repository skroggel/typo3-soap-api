<?php
namespace Madj2k\SoapApi\Command;
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
use Madj2k\SoapApi\Soap\ServerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * class TestCommand
 *
 * Execute on CLI with: 'vendor/bin/typo3 soap_api:test'
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_SoapApi
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TestCommand extends Command
{

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setDescription('Script for testing the SOAP-API.')
            ->addArgument(
                'method',
                InputArgument::OPTIONAL,
                'The method to call',
            )
            ->addArgument(
                'parameters',
                InputArgument::OPTIONAL,
                'Parameter for the method, comma-separated.',
                ''
            );
    }


    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @see \Symfony\Component\Console\Input\InputInterface::bind()
     * @see \Symfony\Component\Console\Input\InputInterface::validate()
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // notting hill
    }


    /**
     * Executes the command for showing sys_log entries
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     * @see \Symfony\Component\Console\Input\InputInterface::bind()
     * @see \Symfony\Component\Console\Input\InputInterface::validate()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $result = 0;
        try {
            $settings = $this->getSettings();
            $method = $input->getArgument('method');
            $parametersRaw = $input->getArgument('parameters');
            $help = (bool) $input->getOption('help');

            $parameters = GeneralUtility::trimExplode(
                ',',
                $parametersRaw
            );

            if (! $method) {
                $help = true;
            }

            if ($help) {

                $io->writeln('This API includes the following methods:');
                $tableHeader = ['method', 'parameters'];
                $io->table($tableHeader, $this->getMethodInfoFromInterface());

            } else {

                $url = sprintf(
                    '%s/index.php?type=%s&wsdl=1',
                    $settings['soapServer']['url'],
                    $settings['soapServer']['typeNum']
                );

                $io->note(
                    sprintf(
                        'Calling method "%s" with parameters "%s" on %s',
                        $method,
                        $parametersRaw,
                        $url
                    )
                );

                $client = new \SoapClient($url, $this->getSoapClientSettings());
                $return = $client->$method(...$parameters);

                $io->writeln(print_r($return, true));
            }

        } catch (\Exception $e) {

            $message = sprintf('An unexpected error occurred while trying to call SOAP: %s',
                str_replace(array("\n", "\r"), '', $e->getMessage())
            );

            // @extensionScannerIgnoreLine
            $io->error($message);
            $result = 1;
        }

        $io->writeln('Done');
        return $result;

    }


    /**
     * Returns a list of methods with their corresponding params
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function getMethodInfoFromInterface(): array
    {
        $tableRows = [];
        $methods = get_class_methods(ServerInterface::class);
        foreach ($methods as $method) {

            $reflection = new \ReflectionMethod (ServerInterface::class, $method);
            $params = $reflection->getParameters();
            $parameterList = [];

            foreach ($params as $cnt => $param) {
                $parameterList[] = sprintf( '%s: %s, %s',
                    ($cnt+1),
                    $param->getName(),
                    $param->getType()->getName()
                );
            }
            $tableRows[] = [
                $method,
                implode("\n", $parameterList)?:'none'
            ];
        }

        return $tableRows;
    }


    /**
     * Gets settings for the SOAP-client
     *
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSoapClientSettings (): array
    {
        $settings = $this->getSettings();
        $clientSettings = [
            'trace' => 1,
            'exceptions' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        ];

        if ($settings['soapServer']['username']) {
            $clientSettings['login'] = $settings['soapServer']['username'];
        }
        if ($settings['soapServer']['password']) {
            $clientSettings['password'] = $settings['soapServer']['password'];
        }
        if ($settings['soapServer']['proxyHost']) {
            $clientSettings['proxy_host'] = $settings['soapServer']['proxyHost'];
        }
        if ($settings['soapServer']['proxyPort']) {
            $clientSettings['proxy_port'] = $settings['soapServer']['proxyPort'];
        }

        return $clientSettings;
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
        return \Madj2k\CoreExtended\Utility\GeneralUtility::getTypoScriptConfiguration('SoapApi', $which);
    }
}
