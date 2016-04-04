<?php
/**
 * Campaign Monitor Magento Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to license@magento.com and you will be sent a copy.
 *
 * @package Campaignmonitor_Createsend
 * @copyright Copyright (c) 2015 Campaign Monitor (https://www.campaignmonitor.com/)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Campaignmonitor_Createsend_OauthController extends Mage_Adminhtml_Controller_Action
{
    const HTTP_OK                   = 200;
    const HTTP_BAD_REQUEST          = 400;
    const HTTP_METHOD_NOT_ALLOWED   = 405;

    const LOG_TOKEN_REDIRECT            = 'Campaign Monitor API Redirect: %s';

    const ERR_OAUTH_EXCHANGE            = 'Error in OAuth Exchange: %s';
    const ERR_INVALID_SCOPE             = 'Invalid state/scope: %s';
    const ERR_UNABLE_TO_AUTHENTICATE    = 'Unable to authenticate: %s';
    const ERR_UNABLE_TO_SAVE_TOKEN      = 'Unable to save OAuth Token: %s';
    const MSG_OAUTH_TOKEN_SAVED         = 'OAuth Token saved.';

    const ADMINHTML_SYSTEM_CONFIG_EDIT  = 'adminhtml/system_config/edit';

    // Disable validation of admin url key to allow redirection from frontend to system config section
    protected $_publicActions = array('getToken');

    /**
     * Responsible for requesting Campaign Monitor OAuth Permission
     * and storage of access_token, expires_in and refresh_token.
     *
     * @link https://www.campaignmonitor.com/api/getting-started/#authenticating_with_oauth
     */
    public function getTokenAction()
    {
        $configSection = array('section' => 'createsend_general');

        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        $helper->log(
            sprintf(self::LOG_TOKEN_REDIRECT, implode(',', $this->getRequest()->getParams())),
            Zend_Log::DEBUG
        );

        $request = $this->getRequest();
        $state = explode(',', $request->getQuery('state'));

        if (sizeof($state) == 2) {
            $scope = $state[0];
            $scopeId = $state[1];
        } else {
            $helper->log(sprintf(self::ERR_INVALID_SCOPE, $state), Zend_Log::DEBUG);

            Mage::getSingleton('adminhtml/session')->addError(sprintf(self::ERR_INVALID_SCOPE, $state));
            return $this->_redirect(self::ADMINHTML_SYSTEM_CONFIG_EDIT, $configSection);
        }

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);
        $websiteId = $scopeModel->getWebsiteIdFromScope($scope, $scopeId);

        $store = Mage::app()->getStore($storeId)->getCode();
        $website = Mage::app()->getWebsite($websiteId)->getCode();

        $configSection['website'] = $website;
        $configSection['store'] = $store;

        $errorMessage = $request->getQuery('error_description');
        if (!empty($errorMessage)) {
            $helper->log(
                sprintf(self::ERR_OAUTH_EXCHANGE, $errorMessage),
                Zend_Log::DEBUG
            );

            Mage::getSingleton('adminhtml/session')->addError(sprintf(self::ERR_UNABLE_TO_AUTHENTICATE, $errorMessage));
            return $this->_redirect(self::ADMINHTML_SYSTEM_CONFIG_EDIT, $configSection);
        }

        $reply = $api->call(
            Zend_Http_Client::POST,
            'token',
            array(),
            array(
                'grant_type'    => 'authorization_code',
                'client_id'     => $helper->getOAuthClientId($storeId),
                'client_secret' => $helper->getOAuthClientSecret($storeId),
                'code'          => $request->getQuery('code'),
                'redirect_uri'  => $api->getOauthRedirectUri($scope, $scopeId),
            ),
            $scope,
            $scopeId
        );

        if (!empty($reply['data']['access_token'])) {
            $result = $helper->saveOauthTokenData($reply['data'], $scope, $scopeId);

            if ($result) {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    sprintf(self::MSG_OAUTH_TOKEN_SAVED, $reply['data']['access_token'])
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addError(
                    sprintf(self::ERR_UNABLE_TO_SAVE_TOKEN, $reply['data']['access_token'])
                );
            }

            $webhookId = $helper->getScopedConfig(Campaignmonitor_Createsend_Helper_Data::XML_PATH_WEBHOOK_ID, $scope, $scopeId);
            $webhookEnabled = $helper->isWebhookEnabled($storeId);

            if ($webhookEnabled && $webhookId === false) {
                // Register webhooks after successfully getting the OAuth token
                /** @var Campaignmonitor_Createsend_Model_Api $api */
                $api = Mage::getModel('createsend/api');
                $reply = $api->updateWebhooks($scope, $scopeId);

                if ($reply['success'] === false) {
                    Mage::getSingleton('adminhtml/session')->addError(
                        sprintf(Campaignmonitor_Createsend_Model_Api::ERR_CANNOT_UPDATE_WEBHOOK, $reply['data']['Message'])
                    );
                } else {
                    if ($reply['data']['Message']) {
                        Mage::getSingleton('adminhtml/session')->addSuccess(sprintf($reply['data']['Message']));
                    }
                }
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                sprintf(self::ERR_UNABLE_TO_AUTHENTICATE, $reply['data']['Message'])
            );
        }

        return $this->_redirect(self::ADMINHTML_SYSTEM_CONFIG_EDIT, $configSection);
    }
}
