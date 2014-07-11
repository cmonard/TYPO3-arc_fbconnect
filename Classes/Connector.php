<?php

namespace Archriss\ArcFbconnect;

//class Connector extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin { // 6.x version
class Connector extends \tslib_pibase {

        public $prefixId = 'tx_arcfbconnect_connector';
        public $scriptRelPath = 'Classes/Connector.php';
        public $extKey = 'arc_fbconnect';
        protected $version45 = FALSE;

        protected function init($conf) {
                $this->conf = $conf;
                $this->pi_USER_INT_obj = TRUE;
                if (version_compare(TYPO3_branch, '6.0', '<')) {
                        $this->version45 = TRUE;
                }
                // We change the path of the script to load the correct LL
                if (!$this->version45) {
                        $this->scriptRelPath = 'Resources/Private/Language/locallang.xlf';
                }
                $this->pi_loadLL();
                if (!$this->version45) {
                        $this->scriptRelPath = 'Classes/Connector.php';
                }
                if ($this->version45) {
                        require_once (\t3lib_extMgm::extPath($this->extKey, 'Classes/Facebook/facebook.php'));
                } else {
                        require_once (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->extKey, 'Classes/Facebook/facebook.php')); // 6.x version
                }
        }

        public function main($content, $conf) {
                $this->init($conf);
                $FBconf = Lib::initFB($this->conf);

                if ($FBconf['appId'] != '' && $FBconf['secret'] != '') {
                        $facebook = new \Facebook($FBconf);

                        // We destroy session on TYPO3 logout
                        if ($this->version45) {
                                $logintype = \t3lib_div::_GET('logintype');
                        } else {
                                $logintype = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('logintype');
                        }
                        if ($logintype == 'logout') {
                                $facebook->destroySession();
                                $url = $this->cObj->currentPageUrl();
                                if ($this->version45) {
                                        \t3lib_utility_Http::redirect($url);
                                } else {
                                        \TYPO3\CMS\Core\Utility\HttpUtility::redirect($url);
                                }
                        }

                        $user_id = $facebook->getUser();

                        // Add more parameter to get
                        $scope = Lib::initiateScopeAndGetList($this->conf['additionalScope.']);
                        $login_url = $facebook->getLoginUrl(array('scope' => $scope));
                        if ($user_id) {
                                try {
                                        $user_profile = $facebook->api('/me', 'GET');
                                        // ajout du parametre loginType=logout pour detruire la session TYPO3 à la volée
                                        $url = $this->cObj->currentPageUrl(array('logintype' => 'logout'));
                                        $content.= '<a href="' . $url . '" class="fb_logout">' . $this->pi_getLL('logout') . '</a>';
                                        // we get the current user:
                                        $created = FALSE;
                                        if (version_compare(TYPO3_branch, '6.0', '<')) {
                                                $localCobj = \t3lib_div::makeInstance('tslib_cObj');
                                        } else {
                                                $localCobj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'); // 6.x version
                                        }
                                        $localCobj->start(Lib::flatten($user_profile));
                                        $username = $localCobj->stdWrap($this->conf['mapping.']['username'], $this->conf['mapping.']['username.']);
                                        if ($username != '') {
                                                $feuser = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'fe_users', '1' . $this->cObj->enableFields('fe_users') . ' AND username LIKE "' . $username . '"');
                                                if (!$feuser) {
                                                        $feuser = Lib::insertFeuser($this->conf['mapping.'], $user_profile);
                                                        $created = TRUE;
                                                }
                                                Lib::autoConnect($feuser);
                                        }
                                        if ($created && intval($this->conf['goToProfilAfterCeate']) > 0) { // If freashly created // redirect to profil to complete required fields
                                                $url = $this->cObj->typoLink_URL(array('parameter' => $this->conf['goToProfilAfterCeate']));
                                                if ($this->version45) {
                                                        \t3lib_utility_Http::redirect($url);
                                                } else {
                                                        \TYPO3\CMS\Core\Utility\HttpUtility::redirect($url);
                                                }
                                        }
                                } catch (FacebookApiException $e) {
                                        // If the user is logged out, you can have a
                                        // user ID even though the access token is invalid.
                                        // In this case, we'll get an exception, so we'll
                                        // just ask the user to login again here.
                                        $content = '<a href="' . $login_url . '" class="fb_relogin">' . $this->pi_getLL('relogin') . '</a>';
                                }
                        } else {
                                $content = '<a href="' . $login_url . '" class="fb_login">' . $this->pi_getLL('login') . '</a>';
                        }
                } else {
                        $content = '<p>Extension isn\'t configured</p>';
                }

                return $content;
        }

}

?>