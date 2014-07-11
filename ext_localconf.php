<?php

if (!defined('TYPO3_MODE'))
        die('Access denied.');

$FBconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['arc_fbconnect']);

if (version_compare(TYPO3_branch, '6.0', '<')) {
        t3lib_extMgm::addPItoST43($_EXTKEY, 'pi/class.tx_arcfbconnect_connector.php', '_connector', 'list_type', 0);

        if ($FBconf['showDebug']) {
                t3lib_extMgm::addPItoST43($_EXTKEY, 'pi/class.tx_arcfbconnect_debug.php', '_debug', 'list_type', 0);
        }
} else {
        \Archriss\ArcFbconnect\Lib::addPItoST43for6x($_EXTKEY, 'Archriss\\ArcFbconnect\\Connector', '_connector', 'list_type', 0);

        if ($FBconf['showDebug']) {
                \Archriss\ArcFbconnect\Lib::addPItoST43for6x($_EXTKEY, 'Archriss\\ArcFbconnect\\Debug', '_debug', 'list_type', 0);
        }
}
?>