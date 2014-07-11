<?php

namespace Archriss\ArcFbconnect;

class Lib {

        static public function addPItoST43for6x($key, $namespace, $prefix = '', $type = 'list_type', $cached = 0) {
                $cN = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($key);
// General plugin
                $pluginContent = trim('
plugin.' . $cN . $prefix . ' = USER' . ($cached ? '' : '_INT') . '
plugin.' . $cN . $prefix . ' {
        userFunc = ' . $namespace . '->main
}');
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($key, 'setup', '
# Setting ' . $key . ' plugin TypoScript
' . $pluginContent);
// After ST43
                switch ($type) {
                        case 'list_type':
                                $addLine = 'tt_content.list.20.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
                                break;
                        case 'menu_type':
                                $addLine = 'tt_content.menu.20.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
                                break;
                        case 'CType':
                                $addLine = trim('
 tt_content.' . $key . $prefix . ' = COA
 tt_content.' . $key . $prefix . ' {
         10 = < lib.stdheader
         20 = < plugin.' . $cN . $prefix . '
 }
 ');
                                break;
                        case 'header_layout':
                                $addLine = 'lib.stdheader.10.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
                                break;
                        case 'includeLib':
                                $addLine = 'page.1000 = < plugin.' . $cN . $prefix;
                                break;
                        default:
                                $addLine = '';
                }
                if ($addLine) {
                        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($key, 'setup', '
# Setting ' . $key . ' plugin TypoScript
' . $addLine . '
', 43);
                }
        }

        static public function autoConnect($userRow) {
                $GLOBALS['TSFE']->fe_user->createUserSession($userRow);
                $GLOBALS['TSFE']->fe_user->loginSessionStarted = TRUE;
                $GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
        }

        static public function initFB($conf = array()) {
                $boolOptions = array('fileUpload', 'trustForwarded', 'allowSignedRequest', 'sharedSession');
                // Get the configuration
                $installConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['arc_fbconnect']);
                $FBconf = is_array($conf['overridingConf.']) && count($conf['overridingConf.']) ? array_merge($installConf, $conf['overridingConf.']) : $installConf;
                foreach ($boolOptions as $boolOption)
                        $FBconf[$boolOption] = ($FBconf[$boolOption] == 'TRUE' || $FBconf[$boolOption] == '1' ) ? TRUE : FALSE;
                if ($FBconf['showDebug'])
                        unset($FBconf['showDebug']);
                return $FBconf;
        }

        static public function initiateScopeAndGetList($scopeArray) {
                if (!is_array($scopeArray))
                        return;
                $return = '';
                foreach ($scopeArray as $mainCat) {
                        $workingArray = self::flatten($mainCat);
                        foreach ($workingArray as $fbProperty => $activated) {
                                if ($activated) {
                                        $return.= ',' . $fbProperty;
                                }
                        }
                }
                return ltrim($return, ',');
        }

        static public function insertFeuser($typo3mapping, $FB_profile, $returnOnlyUserId = FALSE) {
                // if we didn't have both array we return nothing
                if (!is_array($typo3mapping) || !is_array($FB_profile))
                        return NULL;

                // convert date in new field
                $FB_profile['birthday_tstamp'] = strtotime($FB_profile['birthday']);
                if ($FB_profile['work'] && count($FB_profile['work'])) {
                        $GLOBALS['TSFE']->register['FBConnector_workCount'] = count($FB_profile['work']);
                        foreach ($FB_profile['work'] as &$work) {
                                foreach (array('start_date', 'end_date') as $date) {
                                        if ($work[$date]) {
                                                if (version_compare(TYPO3_branch, '6.0', '<')) {
                                                        list ($year, $month, $day) = \t3lib_div::trimExplode('-', $work[$date], 3);
                                                } else {
                                                        list ($year, $month, $day) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('-', $work[$date], 3);
                                                }
                                                $work[$date . '_tstamp'] = mktime(0, 0, 0, $month, $day, $year);
                                        }
                                }
                        }
                }

                $workingArray = self::flatten($FB_profile);

                // instanciate the cObj
                if (version_compare(TYPO3_branch, '6.0', '<')) {
                        $localCobj = \t3lib_div::makeInstance('tslib_cObj');
                } else {
                        $localCobj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'); // 6.x version
                }
                $localCobj->start($workingArray);

                // prepare the insert array
                $insertArray = array();
                foreach ($typo3mapping as $t3Field => $fbField) {
                        $key = rtrim($t3Field, '.'); // key without appending dot

                        if (substr($t3Field, -1) == '.' && isset($typo3mapping[$key])) {
                                continue; // we are in stdWrap properties but we already have done the work on the static propertie
                        }

                        // handle the datas
                        if ((string) $key === (string) $t3Field) {
                                $value = $fbField;
                                $conf = isset($typo3mapping[$key . '.']) ? $typo3mapping[$key . '.'] : array();
                        } else {
                                $value = '';
                                $conf = $fbField;
                        }

                        // insert the stdWrapped value
                        $finalValue = trim($localCobj->stdWrap($value, $conf));
                        if ($finalValue)
                                $insertArray[$key] = $finalValue;
                }

                // call the hooks
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['arc_fbconnect']['insertFeuser'])) {
                        $hookConf['insertArray'] = &$insertArray;
                        $hookConf['typo3mapping'] = $typo3mapping;
                        $hookConf['FB_profile'] = $FB_profile;
                        $hookConf['parentObj'] = &$localCobj;
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['arc_fbconnect']['insertFeuser'] as $key => $classRef) {
                                if (version_compare(TYPO3_branch, '6.0', '<')) {
                                        $_procObj = \t3lib_div::getUserObj($classRef);
                                } else {
                                        $_procObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
                                }
                        }
                        $_procObj->manipulateInsertArray($hookConf, $localCobj);
                }

                // Proceed with the insert
                $created = $GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_users', $insertArray);
                if (!$created)
                        return NULL;
                $feuser = $GLOBALS['TYPO3_DB']->sql_insert_id();

                // return the value
                if ($returnOnlyUserId) {
                        return $feuser;
                } else {
                        return $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'fe_users', 'uid=' . $feuser);
                }
        }

        static public function flatten(array $array, $prefix = '') {
                $flatArray = array();
                if (version_compare(TYPO3_branch, '6.0', '<')) {
                        foreach ($array as $key => $value) {
                                // Ensure there is no trailling dot:
                                $key = rtrim($key, '.');
                                if (!is_array($value)) {
                                        $flatArray[$prefix . $key] = $value;
                                } else {
                                        $flatArray = array_merge($flatArray, self::flatten($value, $prefix . $key . '.'));
                                }
                        }
                } else {
                        $flatArray = \TYPO3\CMS\Core\Utility\ArrayUtility::flatten($array, $prefix);
                }
                return $flatArray;
        }

}

?>