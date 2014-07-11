<?php

namespace Archriss\ArcFbconnect;

//class DummyAdmin extends \TYPO3\CMS\Core\Authentication\BackendUserAuthentication { // 6.x version
class DummyAdmin extends \t3lib_beUserAuth {

        public $user = array(
            'uid' => 0,
            'username' => 'dummyAdminFromFBConnectorDebug',
            'admin' => 1
        );

}

//class Debug extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin { // 6.x version
class Debug extends \tslib_pibase {

        public $prefixId = 'tx_arcfbconnect_debug';
        public $scriptRelPath = 'Classes/Debug.php';
        public $extKey = 'arc_fbconnect';
        protected $version45 = FALSE;

        protected function init($conf) {
                $this->conf = $conf;
                $this->pi_USER_INT_obj = TRUE;
                if (version_compare(TYPO3_branch, '6.0', '<')) {
                        $this->version45 = TRUE;
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
                        $user_id = $facebook->getUser();

                        // We destroy session on TYPO3 logout
                        if ($this->version45) {
                                $refresh = \t3lib_div::_GET('refresh');
                        } else {
                                $refresh = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('refresh');
                        }
                        if ($refresh == '1') {
                                if ($this->version45) {
                                        $dataHandler = \t3lib_div::makeInstance('t3lib_TCEmain');
                                        $dataHandler->start(NULL, NULL, \t3lib_div::makeInstance('Archriss\\ArcFbconnect\\DummyAdmin'));
                                } else {
                                        $dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
                                        $dataHandler->start(NULL, NULL, \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Archriss\\ArcFbconnect\\DummyAdmin'));
                                }
                                $dataHandler->clear_cacheCmd('pages');
                                $facebook->destroySession();
                                $url = $this->cObj->currentPageUrl();
                                if ($this->version45) {
                                        \t3lib_utility_Http::redirect($url);
                                } else {
                                        \TYPO3\CMS\Core\Utility\HttpUtility::redirect($url);
                                }
                        }

                        // Add more parameter to get
                        $scope = Lib::initiateScopeAndGetList($this->conf['additionalScope.']);
                        $content = '<pre>Calling scope: ' . $scope . '</pre>';
                        $login_url = $facebook->getLoginUrl(array('scope' => $scope));
                        if ($user_id) {
                                try {
                                        $user_profile = $facebook->api('/me', 'GET');
                                        $content.= '<pre>' . print_r($user_profile, 1) . '</pre>';
                                        $url = $this->cObj->currentPageUrl(array('refresh' => '1'));
                                        $content.= '<pre>If config change you need to <a href="' . $url . '">refreash</a></pre>';
                                        $content.= '<pre>' . print_r($facebook->api("/me/picture", 'GET', array ('redirect' => false, 'height' => '500', 'type' => 'normal', 'width' => '500')), 1) . '</pre>';
                                } catch (FacebookApiException $e) {
                                        // If the user is logged out, you can have a
                                        // user ID even though the access token is invalid.
                                        // In this case, we'll get an exception, so we'll
                                        // just ask the user to login again here.
                                        $content = '<a href="' . $login_url . '">Show my debug informations.</a> <sub>Don\'t forget to include the static templates.</sub>';
                                }
                        } else {
                                $content = '<a href="' . $login_url . '">Show my debug informations.</a> <sub>Don\'t forget to include the static templates.</sub>';
                        }
                } else {
                        $content = '<p>Extension isn\'t configured, you need to provide appId and Secret in order to communicate with Facebook</p>';
                }

                return $content;
        }

}

?>