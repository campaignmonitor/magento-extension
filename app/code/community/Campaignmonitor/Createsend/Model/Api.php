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

/**
 * Responsible for making calls and getting responses from the Campaign Monitor API.
 * Uses the key authorization mechanism (see the link for further information)
 *
 * @link https://www.campaignmonitor.com/api/getting-started/
 */
class Campaignmonitor_Createsend_Model_Api
{
    const API_BASE_URL      = 'https://api.createsend.com/';
    const API_PATH          = 'api/v3.1/';
    const API_OAUTH_PATH    = 'oauth/';

    const CODE_SUBSCRIBER_NOT_IN_LIST = 203;
    const CODE_DUPLICATE_SEGMENT_TITLE  = 275;
    const CODE_INVALID_SEGMENT_RULES    = 277;
    const CODE_FIELD_KEY_EXISTS         = 255;

    const ERR_API_REQUEST         = 'API Error: %s';
    const ERR_INVALID_HTTP_METHOD = 'The method "%s" is not an acceptable HTTP method';
    const ERR_INVALID_AUTH_METHOD = 'The method "%s" is not an acceptable Authentication method';
    const ERR_INVALID_JSON        = 'The following response is not valid JSON: "%s"';
    const ERR_ID_REQUIRED         = 'The "%s" data is required to make this call';
    const ERR_NO_LIST_ID_AT_SCOPE = 'There is no list ID defined at this %s/%s scope. Cannot create webhook';
    const ERR_CANNOT_UPDATE_WEBHOOK   = 'API Error: Cannot update webhook (%s)';
    const ERR_CANNOT_LIST_WEBHOOKS    = 'API Error: Cannot list webhook (%s)';

    const ERR_CANNOT_CREATE_CUSTOM_FIELD    = 'API Error: Cannot create custom field: (%1$s): %2$s';
    const ERR_CREATE_CUSTOM_FIELDS          = 'Please create these custom fields on the Campaign Monitor list: %s';
    const ERR_SEGMENT_EXISTS            = 'A segment with the same title already exists on this list.';
    const ERR_UNABLE_TO_CREATE_SEGMENT  = 'Unable to create segment (%1$s): %2$s';
    const LOG_SEGMENT_CREATED           = 'Segment successfully created (%s).';

    const LOG_API_REQUEST           = 'API Request %s @ %s: %s';
    const LOG_API_RESPONSE          = 'API Response (%s) @ %s: %s';
    const LOG_CREATED_WEBHOOK       = 'Created webhook with ID "%s"';
    const LOG_DELETED_WEBHOOK       = 'Deleted webhook with ID "%s"';
    const LOG_WEBHOOKS_NOT_ENABLED  = 'Webhooks not enabled.';

    const WEBHOOK_EVENT_SUBSCRIBE   = 'Subscribe';
    const WEBHOOK_EVENT_UPDATE      = 'Update';
    const WEBHOOK_EVENT_DEACTIVATE  = 'Deactivate';

    const SUBSCRIBER_STATUS_ACTIVE      = 'Active';
    const SUBSCRIBER_STATUS_DELETED     = 'Deleted';
    
    const WEBHOOK_STATUS_ACTIVE         = 'Active';
    const WEBHOOK_STATUS_UNSUBSCRIBED   = 'Unsubscribed';
    const WEBHOOK_STATUS_DELETED        = 'Deleted';

    const WEBHOOK_PAYLOAD_FORMAT_JSON   = 'Json';

    const OAUTH_API_TOKEN_REQUEST       = 'oauth_token';

    const FIELD_TYPE_TEXT               = 'Text';
    const FIELD_TYPE_NUMBER             = 'Number';
    const FIELD_TYPE_DATE               = 'Date';
    const FIELD_TYPE_SELECT_ONE         = 'MultiSelectOne';
    const FIELD_TYPE_SELECT_MANY        = 'MultiSelectMany';
    const FIELD_TYPE_COUNTRY            = 'Country';

    const CM_MAX_CUSTOM_FIELD_LENGTH    = 100;
    const CM_CUSTOM_FIELD_PREFIX        = 'Magento ';

    const WISHLIST_CUSTOM_FIELD_PREFIX  = 'Wishlist Item';
    const WISHLIST_CUSTOM_FIELD_PATTERN = '%1$s %2$s %3$s';

    const USER_AGENT_STRING             = 'CM_Magento_Extension; Magento %s; Extension %s; List ID %s';

    // Methods used by the API
    /** @var array */
    protected $_supportedMethods = array(
        Zend_Http_Client::DELETE,
        Zend_Http_Client::GET,
        Zend_Http_Client::POST,
        Zend_Http_Client::PUT
    );

    // Permissions to be requested when authenticating via OAuth
    /** @var array */
    protected $_oauthPermissions = array(
        'ManageLists',
        'ImportSubscribers',
        'ViewTransactional',
        'SendTransactional'
    );

    /**
     * Returns the URL for the OAuth Controller
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getOauthRedirectUri($scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        $url = Mage::getUrl(
            'createsend/oauth/getToken',
            array(
                '_nosid' => true,
                '_store' => $storeId,
                '_type' => 'direct_link'
            )
        );

        return $url;
    }

    /**
     * Returns the URL Address for the Campaign Monitor OAuth Permission Request Page,
     * including all the GET parameters required by the Campaign Monitor API for OAuth
     *
     * @link https://www.campaignmonitor.com/api/getting-started/#authenticating_with_oauth
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getOauthPermissionRequestUrl($scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        return self::API_BASE_URL . self::API_OAUTH_PATH . '?' . http_build_query (
            array(
                'type'          => 'web_server',
                'client_id'     => $helper->getOAuthClientId($storeId),
                'redirect_uri'  => $this->getOauthRedirectUri($scope, $scopeId),
                'scope'         => implode(',', $this->_oauthPermissions),
                'state'         => implode(',', array($scope, $scopeId))
            )
        );
    }

    /**
     * Calculate the version of Magento through a checking of arbitrary magento characteristics.
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        $version = Mage::getVersion();
        $edition = 'ce';

        // If the version is above 1.6.0.0 it might be professional or enterprise.
        if (version_compare($version, '1.6.0.0', '>=')) {

            try {
                // Calculate whether the install has gift cards. if it doesn't, it's community.
                $hasGiftcards = Mage::getModel('enterprise_giftcard/giftcard');
                if ($hasGiftcards) {
                    // Check whether the installation has the enterprise search observer. Only enterprise has this.
                    $hasSolr = Mage::getModel('enterprise_search/observer');

                    if ($hasSolr) {
                        $edition = 'ee';
                    } else {
                        $edition = 'pe';
                    }
                } else {
                    $edition = 'ce';
                }
            } catch(Exception $e){
                Mage::logException($e);
            }

        }

        return sprintf('%s %s', $edition, $version);
    }

    /**
     * Responsible for providing an interface to the API and parsing the data returned from the API into a manageable
     * format.
     *
     * If the API returns a JSON object that is just a string that string will be in the [data][Message] key. This key
     * is also responsible for error messages if the API request fails as well as error messages the API returns.
     *
     * Returns an array of the form
     * [success] => bool
     * [status_code] => int
     * [data] => array
     *     [Message] => string
     *     [DataA] => Returned field
     *     [DataB] => Returned field
     *
     * @param string $method The HTTP method to use. Accepted methods are defined at the top of this class and constants
     *                       are available in the Zend_Http_Client class.
     * @param string $endpoint The API endpoint to query; for example: lists/1eax88123c7cedasdas70fd05saxqwbf
     * @param array $postFields An array of fields to send the end point
     * @param array $queryParams An array of URI query parameters to append to the URI
     * @param string $scope 'default' | 'websites' | 'stores'
     * @param int $scopeId The id of the scope
     *
     * @return array|null
     */
    public function call($method, $endpoint, $postFields = array(), $queryParams = array(), $scope, $scopeId)
    {
        /** @var array $data */
        $data = array(
            'success' => false,
            'status_code' => null,
            'data' => null
        );

        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        $authenticationMethod = $helper->getAuthenticationMethod($storeId);
        if ($endpoint === 'token') {
            $authenticationMethod = self::OAUTH_API_TOKEN_REQUEST;
        }

        $apiKeyOrToken = '';

        switch ($authenticationMethod) {
            case Campaignmonitor_Createsend_Model_Config_AuthenticationMethod::AUTHENTICATION_METHOD_API_KEY:
                $apiKeyOrToken = $helper->getApiKey($scope, $scopeId);

                if (!$apiKeyOrToken) {
                    $data['data']['Message'] = 'No API key set in configuration';
                    return $data;
                }

                break;

            case Campaignmonitor_Createsend_Model_Config_AuthenticationMethod::AUTHENTICATION_METHOD_OAUTH:
                $expiry = new DateTime($helper->getOAuthAccessTokenExpiryDate($storeId));
                $now = new DateTime();
                if ($now > $expiry) {
                    $this->_refreshToken($scope, $scopeId, $storeId);
                }

                $apiKeyOrToken = $helper->getOAuthAccessToken($storeId);

                if (!$apiKeyOrToken) {
                    $data['data']['Message'] = 'No OAuth Token yet. Please request and allow OAuth permission first.';
                    return $data;
                }

                break;
        }

        $mageVersion = strtoupper($this->getMagentoVersion());
        $extVersion = Mage::getConfig()->getModuleConfig("Campaignmonitor_Createsend")->version;
        $listId = $helper->getListId($storeId);

        $userAgent = sprintf(self::USER_AGENT_STRING, $mageVersion, $extVersion, $listId);

        $response = $this->_callApi($authenticationMethod, $apiKeyOrToken, $method, $endpoint, $postFields,
                                    $queryParams, $userAgent);
        if (!$response) {
            $data['data']['Message'] = 'An error occurred during the request.';
            return $data;
        }

        $data['success'] = $response->isSuccessful();

        // Get the response content. The response will either be a JSON encoded object or nothing, depending on the
        // call. The only situation in which content is not JSON is a request URI without a .json file suffix.
        if (stripos($response->getHeader('content-type'), 'application/json') !== null) {
            try {
                $returnContent = Zend_Json::decode($response->getBody());

                // The API sometimes returns a string, not an array.
                if (is_array($returnContent)) {
                    $data['data'] = $returnContent;
                } else {
                    $data['data'] = array('Message' => $returnContent);
                }
            } catch (Zend_Json_Exception $e) {
                $helper->log(sprintf(self::ERR_INVALID_JSON, $response->getBody()));
            }
        }

        $data['status_code'] = $response->getStatus();
        return $data;
    }

    /**
     * Responsible for making the actual calls to the API. The authorization method is either "API Key" or "OAuth"
     * authorisation method, and documentation can be found at the link below.
     *
     * @link https://www.campaignmonitor.com/api/
     *
     * @param string $authenticationMethod The Authentication Method to be used for the request ('api_key' or 'oauth')
     * @param string $apiKeyOrToken The API key or OAuth Access Token required to make the API request
     * @param string $method The HTTP Method for the request. Valid methods are documented at the top of the class
     * @param string $endpoint The endpoint to make the request of
     * @param array $postFields The fields that should be posted to the end point
     * @param array $queryParams Any query params that should be appended to the request
     * @param string $userAgent HTTP User-Agent to use
     *
     * @return Zend_Http_Response
     * @throws Mage_Core_Exception if the method given is not an accepted method
     * @throws Zend_Http_Client_Exception if something goes wrong during the connection
     */
    protected function _callApi($authenticationMethod, $apiKeyOrToken, $method, $endpoint,
                                $postFields = array(), $queryParams = array(), $userAgent)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        if (in_array($method, $this->_supportedMethods) === false) {
            Mage::throwException(sprintf(self::ERR_INVALID_HTTP_METHOD, $method));
        }

        // Construct the client
        $client = new Zend_Http_Client();
        $client->setMethod($method);
        $client->setConfig(array('useragent' => $userAgent));

        $uri = self::API_BASE_URL . self::API_PATH . $endpoint . '.json';

        switch ($authenticationMethod) {
            case Campaignmonitor_Createsend_Model_Config_AuthenticationMethod::AUTHENTICATION_METHOD_API_KEY:
                $client->setAuth($apiKeyOrToken, null, Zend_Http_Client::AUTH_BASIC);
                break;

            case Campaignmonitor_Createsend_Model_Config_AuthenticationMethod::AUTHENTICATION_METHOD_OAUTH:
                $client->setHeaders('Authorization', 'Bearer ' . $apiKeyOrToken);
                break;

            case self::OAUTH_API_TOKEN_REQUEST:
                $uri = self::API_BASE_URL . self::API_OAUTH_PATH . $endpoint;
                break;

            default:
                Mage::throwException(sprintf(self::ERR_INVALID_AUTH_METHOD, $authenticationMethod));
                break;
        }

        if (count($queryParams) > 0) {
            $uri .= '?' . http_build_query($queryParams);
        }
        $client->setUri($uri);

        $payload = Zend_Json::encode($postFields);

        // Optionally set the POST payload
        if ($method === Zend_Http_Client::POST) {
            $client->setRawData($payload, 'application/json');
        }

        // Log the request for debugging
        $helper->log(
            sprintf(self::LOG_API_REQUEST, $method, $client->getUri()->__toString(), $payload),
            Zend_Log::DEBUG
        );

        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Exception $e) {
            $helper->log($e->getMessage(), Zend_Log::WARN);
            return false;
        }

        // Log response
        $helper->log(
            sprintf(self::LOG_API_RESPONSE, $response->getStatus(), $client->getUri()->__toString(), $response->getBody()),
            Zend_Log::DEBUG
        );

        return $response;
    }

    /**
     * Refreshes and saves the OAuth Access Token
     *
     * @param string $scope
     * @param int $scopeId
     * @param int $storeId
     * @return bool
     */
    protected function _refreshToken($scope, $scopeId, $storeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        // refresh access token
        $response = $this->call(
            Zend_Http_Client::POST,
            'token',
            array(),
            array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $helper->getOAuthRefreshToken($storeId),
            ),
            $scope,
            $scopeId
        );

        return $helper->saveOauthTokenData($response['data'], $scope, $scopeId);
    }

    /**
     * Updates the webhooks based for scope/scopeId.
     *
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    public function updateWebhooks($scope, $scopeId)
    {
        $data = array(
            'success' => false,
            'data' => null
        );

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);
        $store = Mage::app()->getStore($storeId);

        $webhookUrl = Mage::getUrl('createsend/webhooks/index', array('_store' => $store));

        /** @var Mage_Core_Model_Config $configSingleton */
        $configSingleton = Mage::getConfig();

        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $webhookEnabled = $helper->isWebhookEnabled($storeId);

        // Magento's normal config system will inherit all values from whatever parent object sets them. While this
        // is OK for usage we cannot delete or manipulate a webhook based on an inherited value. Therefore we call
        // a method that directly queries the core_config_data table, based on the scope values in the request.
        $listId = $helper->getScopedConfig(Campaignmonitor_Createsend_Helper_Data::XML_PATH_LIST_ID, $scope, $scopeId);

        if ($listId === false) {
            $data['data']['Message'] = sprintf(self::ERR_NO_LIST_ID_AT_SCOPE, $scope, $scopeId);
            return $data;
        }

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        $this->deleteAllWebhooks($listId, $scope, $scopeId);

        if ($webhookEnabled) {
            $result = $api->call(
                Zend_Http_Client::POST,
                "lists/{$listId}/webhooks",
                array(
                    'Events' => array(
                        Campaignmonitor_Createsend_Model_Api::WEBHOOK_EVENT_SUBSCRIBE,
                        Campaignmonitor_Createsend_Model_Api::WEBHOOK_EVENT_UPDATE,
                        Campaignmonitor_Createsend_Model_Api::WEBHOOK_EVENT_DEACTIVATE
                    ),
                    'Url' => $webhookUrl,
                    'PayloadFormat' => Campaignmonitor_Createsend_Model_Api::WEBHOOK_PAYLOAD_FORMAT_JSON
                ),
                array(),
                $scope,
                $scopeId
            );

            if ($result['success'] === false) {
                return $result;
            }

            $configSingleton->saveConfig(
                Campaignmonitor_Createsend_Helper_Data::XML_PATH_WEBHOOK_ID,
                $result['data']['Message'], $scope, $scopeId
            );

            $helper->log(sprintf(self::LOG_CREATED_WEBHOOK, $result['data']['Message']));

            $data['success'] = true;
            $data['data']['Message'] = sprintf(self::LOG_CREATED_WEBHOOK, $result['data']['Message']);
        } else {
            $data['success'] = true;
            $data['data']['Message'] = self::LOG_WEBHOOKS_NOT_ENABLED;
        }

        return $data;
    }

    /**
     *  Deletes all webhooks registered for the list.
     *
     * @param string $listId Campaign Monitor List ID
     * @param string $scope
     * @param int $scopeId
     */
    public function deleteAllWebhooks($listId, $scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");

        // Get webhook id from config data and try to remove it
        $webhookId = $helper->getScopedConfig(Campaignmonitor_Createsend_Helper_Data::XML_PATH_WEBHOOK_ID, $scope, $scopeId);
        if ($webhookId !== false) {
            Mage::getConfig()->deleteConfig($helper::XML_PATH_WEBHOOK_ID, $scope, $scopeId);
            $this->_deleteWebhook($listId, $webhookId, $scope, $scopeId);
        }

        // Then try to remove all other remaining webhooks, if any
        $result = $api->call(
            Zend_Http_Client::GET,
            "lists/{$listId}/webhooks",
            array(),
            array(),
            $scope,
            $scopeId
        );

        if ($result['success'] !== false) {
            foreach ($result['data'] as $webhookData) {
                $webhookId = $webhookData['WebhookID'];
                $this->_deleteWebhook($listId, $webhookId, $scope, $scopeId);
            }
        }
    }

    /**
     * Deletes a webhook previously registered in Campaign Monitor.
     *
     * @param string $listId Campaign Monitor List ID
     * @param string $webhookId The webhook to de-register
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    protected function _deleteWebhook($listId, $webhookId, $scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        /** @var Mage_Core_Model_Config $configSingleton */
        $configSingleton = Mage::getConfig();

        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $result = $api->call(
            Zend_Http_Client::DELETE,
            "lists/{$listId}/webhooks/{$webhookId}",
            array(),
            array(),
            $scope,
            $scopeId
        );

        if ($result['success'] === false) {
            return $result;
        } else {
            $helper->log(sprintf(self::LOG_DELETED_WEBHOOK, $webhookId), Zend_Log::NOTICE);
        }

        $savedWebhookId = $helper->getScopedConfig(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_WEBHOOK_ID, $scope, $scopeId
        );

        if ($savedWebhookId === $webhookId) {
            $configSingleton->deleteConfig($helper::XML_PATH_WEBHOOK_ID, $scope, $scopeId);
        }

        return $result;
    }

    /**
     * Subscribes an email address to CM. The list ID will be retrieved from the configuration using the scope/scopeId.
     *
     * @param string $email The email address to subscribe
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    public function subscribe($email, $scope, $scopeId)
    {
        $subscriberData = array(
            'EmailAddress' => $email,
            'Resubscribe' => true,
            'RestartSubscriptionBasedAutoresponders' => true
        );

        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);
        $websiteId = $scopeModel->getWebsiteIdFromScope($scope, $scopeId);

        /* @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel("customer/customer")
            ->setWebsiteId($websiteId)
            ->loadByEmail($email);

        if ($customer->getId()) {
            $subscriberData['Name'] = $customer->getName();
        }

        $subscriberData['CustomFields'] =
            Campaignmonitor_Createsend_Model_Customer_Observer::generateCustomFields($customer);

        return $this->call(
            Zend_Http_Client::POST,
            'subscribers/' . $helper->getListId($storeId),
            $subscriberData,
            array(),
            $scope,
            $scopeId
        );
    }

    /**
     * Un-subscribes an email address from CM list of scope/scopeId
     *
     * @param string $email The email to un-subscribe from CM
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    public function unsubscribe($email, $scope, $scopeId)
    {
        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        return $this->call(
            Zend_Http_Client::POST,
            "subscribers/{$helper->getListId($storeId)}/unsubscribe",
            array(
                'EmailAddress' => $email
            ),
            array(),
            $scope,
            $scopeId
        );
    }

    /**
     * Returns the Campaign Monitor Custom Field name with the prefix and truncated to max length
     *
     * @param $fieldName
     * @return string
     */
    public function formatCustomFieldName($fieldName)
    {
        $customName = self::CM_CUSTOM_FIELD_PREFIX . $fieldName;

        // Return only up to CM_MAX_CUSTOM_FIELD_LENGTH characters to avoid error
        return substr($customName, 0, self::CM_MAX_CUSTOM_FIELD_LENGTH);
    }

    /**
     * Creates a Campaign Monitor custom field on the list defined in the scope/scopeid using the API.
     *
     * @param string $fieldName The name of the custom field to be created.
     * @param string $dataType Data Type, can be either: Text, Number, Date, MultiSelectOne, MultiSelectMany, Country or USState.
     * @param array $options Array of options if type is MultiSelectOne or MultiSelectMany
     * @param bool $isVisibleInPreferenceCenter Whether the field should be visible in the subscriber preference center.
     * @param string $scope Scope
     * @param int $scopeId Scope ID
     * @return array|null
     */
    public function createCustomField($fieldName, $dataType, $options, $isVisibleInPreferenceCenter, $scope, $scopeId)
    {
        $params = array(
            'FieldName'                 => $this->formatCustomFieldName($fieldName),
            'DataType'                  => $dataType,
            'VisibleInPreferenceCenter' => $isVisibleInPreferenceCenter,
        );
        if (is_array($options) && count($options)
            && ($dataType == self::FIELD_TYPE_SELECT_ONE || $dataType == self::FIELD_TYPE_SELECT_MANY)
        ){
            $params['Options'] = $options;
        }

        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");

        $listId = $helper->getScopedConfig($helper::XML_PATH_LIST_ID, $scope, $scopeId);

        return $this->call(
            Zend_Http_Client::POST,
            "lists/{$listId}/customfields",
            $params,
            array(),
            $scope,
            $scopeId
        );
    }

    /**
     * Creates Campaign Monitor customer custom fields on the list id defined in the scope.
     *
     * @param string $scope
     * @param int $scopeId
     * @return array List of errors, grouped by error message
     */
    public function createCustomerCustomFields($scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getSingleton('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        /** @var Campaignmonitor_Createsend_Model_Config_CustomerAttributes $attrSource */
        $attrSource = Mage::getSingleton('createsend/config_customerAttributes');

        $linkedAttributes = @unserialize(Mage::getStoreConfig($helper::XML_PATH_M_TO_CM_ATTRIBUTES, $storeId));

        $errors = array();

        foreach ($linkedAttributes as $la) {
            $magentoAtt = $la['magento'];
            $cmAtt = $attrSource->getCustomFieldName($la['magento'], true);
            $dataType = $attrSource->getFieldType($magentoAtt);
            $options = $attrSource->getFieldOptions($magentoAtt);

            $reply = $this->createCustomField(
                $cmAtt,
                $dataType,
                $options,
                false,
                $scope,
                $scopeId
            );

            if ($reply['success'] === false) {
                // Ignore 'field name already exists' errors
                if ($reply['data']['Code'] != self::CODE_FIELD_KEY_EXISTS) {
                    $message = $reply['data']['Message'];

                    if (!isset($errors[$message])) {
                        $errors[$message] = array();
                    }

                    $errors[$message][] = $this->formatCustomFieldName($cmAtt);
                }
            }
        }

        return $errors;
    }

    /**
     * Creates Campaign Monitor wishlist custom fields on the list id defined in the scope.
     *
     * @param string $scope
     * @param int $scopeId
     * @return array List of errors, grouped by error message
     */
    public function createWishlistCustomFields($scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getSingleton('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        /** @var Campaignmonitor_Createsend_Model_Config_ProductAttributes $attrSource */
        $attrSource = Mage::getSingleton('createsend/config_productAttributes');

        // Create wishlist custom fields
        $maxWishlistItems = $helper->getMaxWistlistItems($storeId);
        $productAttributes = @unserialize(Mage::getStoreConfig($helper::XML_PATH_WISHLIST_PRODUCT_ATTRIBUTES, $storeId));

        if (empty($productAttributes) && empty($maxWishlistItems)) {
            return array();
        }

        $options = array();
        $errors = array();

        foreach ($productAttributes as $pa) {
            $magentoAtt = $pa['magento'];
            $cmAtt = $attrSource->getCustomFieldName($pa['magento'], true);
            $dataType = $attrSource->getFieldType($magentoAtt);

            for ($i = 1; $i <= $maxWishlistItems; $i++) {
                $fieldName = sprintf(self::WISHLIST_CUSTOM_FIELD_PATTERN, self::WISHLIST_CUSTOM_FIELD_PREFIX, $i, $cmAtt);
                $reply = $this->createCustomField(
                    $fieldName,
                    $dataType,
                    $options,
                    false,
                    $scope,
                    $scopeId
                );

                if ($reply['success'] === false) {
                    // Ignore 'field name already exists' errors
                    if ($reply['data']['Code'] != self::CODE_FIELD_KEY_EXISTS) {
                        $message = $reply['data']['Message'];

                        if (!isset($errors[$message])) {
                            $errors[$message] = array();
                        }

                        $errors[$message][] = $this->formatCustomFieldName($cmAtt);
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Creates all the example segments as defined in Campaignmonitor_Createsend_Model_Config_ExampleSegments.
     * Returns an array of status and message per segment creation call.
     *
     * @param string $scope
     * @param int $scopeId
     * @return array
     */
    public function createExampleSegments($scope, $scopeId)
    {
        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");

        $listId = $helper->getScopedConfig($helper::XML_PATH_LIST_ID, $scope, $scopeId);

        $segments = Mage::getSingleton('createsend/config_exampleSegments')->getExampleSegments();

        /** @var array $response */
        $responses = array();

        foreach ($segments as $segmentKey => $segment) {
            $reply = $this->call(
                Zend_Http_Client::POST,
                'segments/' . $listId,
                $segment,
                array(),
                $scope,
                $scopeId
            );

            $status = 'success';
            if ($reply['success'] === false) {
                $status = 'error';
                $message = sprintf(self::ERR_UNABLE_TO_CREATE_SEGMENT, $segment['Title'], $reply['data']['Message']);
                switch ($reply['data']['Code']) {
                    case self::CODE_DUPLICATE_SEGMENT_TITLE:
                        $status = 'warning';
                        $message .= ' -- ' . self::ERR_SEGMENT_EXISTS;
                        break;
                    case self::CODE_INVALID_SEGMENT_RULES:
                        $pos = strpos($segmentKey, ':');
                        if ($pos === FALSE) {
                            $requiredFields = $segmentKey;
                        } else {
                            $requiredFields = substr($segmentKey, $pos + 1);
                        }

                        $message .= ' -- ' . sprintf(self::ERR_CREATE_CUSTOM_FIELDS, $requiredFields);
                        break;
                }
            } else {
                $message = sprintf(self::LOG_SEGMENT_CREATED, $segment['Title']);
            }

            $responses[] = array(
                'status'    => $status,
                'message'   => $message
            );
        }

        return $responses;
    }
}
