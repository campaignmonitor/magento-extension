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

class Campaignmonitor_Createsend_Helper_Data extends Mage_Core_Helper_Data
{
    const LOGFILE = "campaignmonitor_createsend.log";

    const XML_PATH_WEBHOOK_ID                       = 'createsend_general/api/webhook_id';
    const XML_PATH_LIST_ID                          = 'createsend_general/api/list_id';
    const XML_PATH_NEW_LIST_NAME                    = 'createsend_general/api/new_list_name';
    const XML_PATH_AUTHENTICATION_METHOD            = 'createsend_general/api/authentication_method';
    const XML_PATH_API_ID                           = 'createsend_general/api/api_key';
    const XML_PATH_API_CLIENT_ID                    = 'createsend_general/api/api_client_id';
    const XML_PATH_OAUTH_CLIENT_ID                  = 'createsend_general/api/oauth_client_id';
    const XML_PATH_OAUTH_CLIENT_SECRET              = 'createsend_general/api/oauth_client_secret';
    const XML_PATH_MAX_WISHLIST_ITEMS               = 'createsend_customers/wishlists/max_wishlist_items';
    const XML_PATH_WEBHOOK_ENABLED                  = 'createsend_general/advanced/webhook_enabled';
    const XML_PATH_OAUTH_ACCESS_TOKEN               = 'createsend_general/api/oauth_access_token';
    const XML_PATH_OAUTH_ACCESS_TOKEN_EXPIRY_DATE   = 'createsend_general/api/oauth_access_token_expiry_date';
    const XML_PATH_OAUTH_REFRESH_TOKEN              = 'createsend_general/api/oauth_refresh_token';
    const XML_PATH_TRANSACTIONAL_EMAIL_ENABLED      = 'createsend_transactional/emails/transactional_email_enabled';
    const XML_PATH_EMAIL_RETENTION_DAYS             = 'createsend_transactional/emails/transactional_email_retention_days';
    const XML_PATH_SUBSCRIBER_SYNC_ENABLED          = 'createsend_general/advanced/subscriber_synchronisation_enabled';
    const XML_PATH_SUBSCRIBER_SYNC_RESOLUTION_METHOD = 'createsend_general/advanced/subscriber_synchronisation_resolution_method';
    const XML_PATH_SUBSCRIBER_SYNC_PREFERRED_SOURCE = 'createsend_general/advanced/subscriber_synchronisation_preferred_source';
    const XML_PATH_MAGENTO_CRON_ENABLED             = 'createsend_general/advanced/magento_cron_enabled';
    const XML_PATH_LOGGING                          = 'createsend_general/advanced/logging';
    const XML_PATH_M_TO_CM_ATTRIBUTES               = 'createsend_customers/attributes/m_to_cm_attributes';
    const XML_PATH_WISHLIST_PRODUCT_ATTRIBUTES      = 'createsend_customers/wishlists/wishlist_product_attributes';

    protected $canLog = null;

    /**
     * Logs all extension specific notices to a separate file
     *
     * @param string $message The message to log
     * @param int $level The log level (defined in the Zend_Log class)
     */
    public function log($message, $level = Zend_Log::DEBUG)
    {
        if ($this->canLog()) {
            Mage::log($message, $level, self::LOGFILE);
        }
    }

    /**
     * @return bool
     */
    public function canLog()
    {
        if ($this->canLog === null) {
            $this->canLog = Mage::getStoreConfig(self::XML_PATH_LOGGING);
        }
        return $this->canLog;
    }

    /**
     * Returns the authentication method configuration value.
     * No need to trim as value comes from source model and not from user input.
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getAuthenticationMethod($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_AUTHENTICATION_METHOD, $store);
    }

    /**
     * Returns a sanitized version of the API key configuration value
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getApiKey($scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getSingleton('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        return urlencode(trim($this->getStoreConfig(self::XML_PATH_API_ID, $storeId)));
    }

    /**
     * Returns a sanitized version of the API key Client ID configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getApiClientId($store = null)
    {
        return urlencode(trim(Mage::getStoreConfig(self::XML_PATH_API_CLIENT_ID, $store)));
    }

    /**
     * Returns a sanitized version of the list id configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getListId($store = null)
    {
        // Get value from database directly, bypassing the config cache
        // because we are saving the config value using Mage:getConfig()->saveConfig()
        return urlencode(trim($this->getStoreConfig(self::XML_PATH_LIST_ID, $store)));
    }

    /**
     * Returns the new list name configuration value.
     *
     * @param mixed $store
     * @return string
     */
    public function getNewListName($store = null)
    {
        return trim(Mage::getStoreConfig(self::XML_PATH_NEW_LIST_NAME, $store));
    }

    /**
     * Returns the OAuth Client ID configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getOAuthClientId($store = null)
    {
        return trim(Mage::getStoreConfig(self::XML_PATH_OAUTH_CLIENT_ID, $store));
    }

    /**
     * Returns the OAuth Client Secret configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getOAuthClientSecret($store = null)
    {
        return trim(Mage::getStoreConfig(self::XML_PATH_OAUTH_CLIENT_SECRET, $store));
    }

    /**
     * Returns the OAuth Access Token configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getOAuthAccessToken($store = null)
    {
        return $this->getStoreConfig(self::XML_PATH_OAUTH_ACCESS_TOKEN, $store);
    }

    /**
     * Returns the OAuth Access Token configuration value in the specified store
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getOAuthAccessTokenExpiryDate($store = null)
    {
        return $this->getStoreConfig(self::XML_PATH_OAUTH_ACCESS_TOKEN_EXPIRY_DATE, $store);
    }

    /**
     * Returns the OAuth Refresh Token in the specified store
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getOAuthRefreshToken($store = null)
    {
        return $this->getStoreConfig(self::XML_PATH_OAUTH_REFRESH_TOKEN, $store);
    }

    /**
     * Returns the Max Wish list items
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return int
     */
    public function getMaxWistlistItems($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_MAX_WISHLIST_ITEMS, $store);
    }

    /**
     * Returns the Wishlist product attributes for the custom fields
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return int
     */
    public function getProductAttributes($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_WISHLIST_PRODUCT_ATTRIBUTES, $store);
    }

    /**
     * Returns the Webhook enabled configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return bool
     */
    public function isWebhookEnabled($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_WEBHOOK_ENABLED, $store);
    }

    /**
     * Returns the Transactional Email configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return bool
     */
    public function isTransactionalEmailsEnabled($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_TRANSACTIONAL_EMAIL_ENABLED, $store);
    }

    /**
     * Returns the Subscriber Synchronisation Enabled configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return bool
     */
    public function isSubscriberSynchronisationEnabled($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SUBSCRIBER_SYNC_ENABLED, $store);
    }

    /**
     * Returns the Subscriber Synchronisation Resolution Method configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return string
     */
    public function getSubscriberSynchronisationResolutionMethod($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_SUBSCRIBER_SYNC_RESOLUTION_METHOD, $store);
    }

    /**
     * Returns the Subscriber Synchronisation Preferred Source configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return mixed
     */
    public function getSubscriberSynchronisationPreferredSource($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_SUBSCRIBER_SYNC_PREFERRED_SOURCE, $store);
    }

    /**
     * Returns the Magento Cron Enabled configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return bool
     */
    public function isMagentoCronEnabled($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_MAGENTO_CRON_ENABLED, $store);
    }

    /**
     * Returns the Transactional Email Header Retention Days configuration value
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return int
     */
    public function getEmailRetentionDays($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_RETENTION_DAYS, $store);
    }

    /**
     * Returns the Campaign Monitor Client ID
     *
     * @param mixed $store Get the configuration value for this store code or ID
     * @return null|string
     */
    public function getClientId($store)
    {
        return $this->getApiClientId($store);
    }

    /**
     * Saves the token data for OAuth API
     *
     * @param array $data Array containing the access token ($data['access_token']),
     *                    expiry period in seconds ($data['expires_in'])
     *                    and refresh token ($data['refresh_token'])
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    public function saveOauthTokenData(array $data, $scope, $scopeId)
    {
        if (empty($data['access_token'])) {
            return false;
        }

        $now = new DateTime();
        $expiry = $now->add(DateInterval::createFromDateString($data['expires_in'] . ' seconds'));

        /** @var Mage_Core_Model_Config $configSingleton */
        $configSingleton = Mage::getConfig();

        $configSingleton->saveConfig(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_OAUTH_ACCESS_TOKEN,
            $data['access_token'], $scope, $scopeId
        );

        $configSingleton->saveConfig(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_OAUTH_ACCESS_TOKEN_EXPIRY_DATE,
            $expiry->format('Y-m-d H:i:s O'), $scope, $scopeId
        );

        $configSingleton->saveConfig(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_OAUTH_REFRESH_TOKEN,
            $data['refresh_token'], $scope, $scopeId
        );

        return true;
    }

    /**
     * Returns true if the configuration for the specified scope/scopeId is complete.
     *
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    function isCompleteConfig($scope, $scopeId)
    {
        $reply = true;

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getSingleton('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        $authenticationMethod = $this->getAuthenticationMethod($storeId);
        switch ($authenticationMethod) {
            case Campaignmonitor_Createsend_Model_Config_AuthenticationMethod::AUTHENTICATION_METHOD_API_KEY:
                $apiKey = $this->getApiKey($scope, $scopeId);

                if (!$apiKey) {
                    $reply = false;
                }
                break;

            case Campaignmonitor_Createsend_Model_Config_AuthenticationMethod::AUTHENTICATION_METHOD_OAUTH:
                $clientId = $this->getOAuthClientId($storeId);
                $clientSecret = $this->getOAuthClientSecret($storeId);
                $oauthAccessToken = $this->getOAuthAccessToken($storeId);

                if (!$clientId || !$clientSecret || !$oauthAccessToken) {
                    $reply = false;
                }
                break;

            default:
                $reply = false;
                break;

        }

        $listId = $this->getScopedConfig(Campaignmonitor_Createsend_Helper_Data::XML_PATH_LIST_ID, $scope, $scopeId);

        if ($listId === false) {
            $reply = false;
        }

        return $reply;
    }

    /**
     * Gets the appropriate configuration value from the database, given the path and store code or ID.
     * If the configuration is not found in the store scope, the value for website scope and default scope
     * will be retrieved.
     *
     * This function is written to work the same way as Mage::getStoreConfig() except that it does not
     * use the config cache. This function should be used if there are config settings that
     * were saved directly using Mage::getConfig()->saveConfig()
     *
     * @param string $path
     * @param mixed $store
     * @return mixed|null
     */
    public function getStoreConfig($path, $store)
    {
        $configValue = $this->getScopedConfig($path, 'stores', Mage::app()->getStore($store)->getId());
        if ($configValue === false) {
            $configValue = $this->getScopedConfig($path, 'websites', Mage::app()->getStore($store)->getWebsiteId());
            if ($configValue === false) {
                $configValue = $this->getScopedConfig($path, 'default', Mage_Core_Model_App::ADMIN_STORE_ID);
                if ($configValue === false) {
                    $configValue = null;
                }
            }
        }

        return $configValue;
    }

    /**
     * Gets the appropriate configuration value from the database, given the path, scope and ID. Uses similar logic
     * to the method Mage_Core_Model_Resource_Config::deleteConfig(), and should only be used when querying a config
     * value known to be in the DB and where scope must be known.
     *
     * @param string $path the tree path
     * @param string $scope default, website or store scope
     * @param int $scopeId The ID of the scoped reference
     * @return mixed
     */
    public function getScopedConfig($path, $scope, $scopeId)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $db */
        // Uses the core_write table instead of core_read to get up to date information as config is iteratively saved.
        $db = $resource->getConnection(
            Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE
        );

        $coreConfigTable = $resource->getTableName('core/config_data');
        $select = $db->select()
            ->from($coreConfigTable, 'value')
            ->where('path = ?', $path)
            ->where('scope = ?', $scope)
            ->where('scope_id = ?', $scopeId);

        $value = $db->fetchOne($select);
        if ($value !== null) {
            return $value;
        }

        return false;
    }
}
