<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey)
    {

        //=================================================================
        // Configure Plugin
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'Api',
            array(
                'Soap' => 'soap, wsdl',

            ),
            // non-cacheable actions
            array(
                'Soap' => 'soap, wsdl',
            )
        );

        //=================================================================
        // Add TypoScript automatically
        //=================================================================
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
            'SoapApi',
            'constants',
            '<INCLUDE_TYPOSCRIPT: source="FILE: EXT:soap_api/Configuration/TypoScript/constants.typoscript">'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
            'SoapApi',
            'setup',
            '<INCLUDE_TYPOSCRIPT: source="FILE: EXT:soap_api/Configuration/TypoScript/setup.typoscript">'
        );

        //=================================================================
        // Register Logger
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Madj2k']['SoapApi']['writerConfiguration'] = array(

            // configuration for WARNING severity, including all
            // levels with higher severity (ERROR, CRITICAL, EMERGENCY)
            \TYPO3\CMS\Core\Log\LogLevel::DEBUG => array(
                // add a FileWriter
                'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => array(
                    // configuration for the writer
                    'logFile' => \TYPO3\CMS\Core\Core\Environment::getVarPath()  . '/log/tx_soapapi.log'
                )
            ),
        );

    },
    $_EXTKEY
);


