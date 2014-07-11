<?php

if (!defined('TYPO3_MODE'))
        die('Access denied.');

$FBconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['arc_fbconnect']);

if (version_compare(TYPO3_branch, '6.0', '<')) {
        t3lib_div::loadTCA('tt_content');
}
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key,pages';

// field Add
$tempColumns = array(
    'tx_arcfbconnect_birthday' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:arc_fbconnect/Resources/Private/Language/locallang_db.xlf:fe_users.tx_arcfbconnect_birthday',
        'config' => array(
            'type' => 'input',
            'size' => 6,
            'eval' => 'date',
            'checkbox' => 1,
        ),
    ),
    'tx_arcfbconnect_fbID' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:arc_fbconnect/Resources/Private/Language/locallang_db.xlf:fe_users.tx_arcfbconnect_fbID',
        'config' => array(
            'type' => 'input',
            'size' => 20,
            'eval' => 'int',
            'checkbox' => 1,
        ),
    ),
);

if (version_compare(TYPO3_branch, '6.0', '<')) {
        $tempColumns['tx_arcfbconnect_birthday']['label'] = 'LLL:EXT:arc_fbconnect/locallang_db.xml:fe_users.tx_arcfbconnect_birthday';
        $tempColumns['tx_arcfbconnect_fbID']['label'] = 'LLL:EXT:arc_fbconnect/locallang_db.xml:fe_users.tx_arcfbconnect_fbID';

        t3lib_extMgm::addPlugin(array(
            'Arc FB Connector',
            $_EXTKEY . '_connector',
            t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
                ), 'list_type');

        t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Archriss FB Connector configurations');

        if ($FBconf['showDebug'])
                t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript/Debug', 'Archriss FB Connector debug, include after the main static file');

        t3lib_div::loadTCA('fe_users');
        t3lib_extMgm::addTCAcolumns('fe_users', $tempColumns, 1);
        t3lib_extMgm::addToAllTCAtypes('fe_users', 'tx_arcfbconnect_birthday;;;;1-1-1', '', 'before:address');
        t3lib_extMgm::addToAllTCAtypes('fe_users', 'tx_arcfbconnect_fbID;;;;1-1-1', '', 'after:username');
} else {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array(
            'Arc FB Connector',
            $_EXTKEY . '_connector',
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'ext_icon.gif'
                ), 'list_type');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Archriss FB Connector configurations');

        if ($FBconf['showDebug'])
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/Debug', 'Archriss FB Connector debug, include after the main static file');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns, 1);
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'tx_arcfbconnect_birthday;;;;1-1-1', '', 'before:address');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'tx_arcfbconnect_fbID;;;;1-1-1', '', 'after:username');
}
?>