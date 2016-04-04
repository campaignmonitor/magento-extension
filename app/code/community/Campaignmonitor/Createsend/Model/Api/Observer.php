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
 * Responsible for taking action using the api model as a result of some dispatched Magento event
 */
class Campaignmonitor_Createsend_Model_Api_Observer
{
    const ERR_CANNOT_CREATE_SUBSCRIBER = 'Api Error: Could not create subscription for "%s" ("%s")';
    const ERR_NO_EMAIL_PARAMETER       = 'There was no email parameter supplied in this request';

    const MSG_CANNOT_CREATE_SUBSCRIBER = 'Could not create subscriber "%s": An api error occurred: %s';

    const LOG_ADD_SUBSCRIBER = 'Adding newsletter subscription via frontend Sign up block for "%s"';
    const LOG_CUSTOMER_NOT_LOGGED_IN = 'Not subscribed, customer is not logged in: "%s"';

    const MSG_NEW_LIST_CREATED          = 'New Campaign Monitor list created.';
    const MSG_CONNECT_SUCCESS           = 'Your Magento account has been successfully connected, please allow some time for your data to be synced.';
    const MSG_PRE_CREATED_CONFIG        = 'To help get you started we\'ve already mapped some of your subscriber data and created pre-packaged segments in Campaign Monitor, <a href="https://login.createsend.com/l" target="_blank">log in to your account</a> to check them out.';

    const SUBSCRIBER_DEFAULT_NAME = '(Guest)';

    /**
     * Updates the Deactivate webhook on a config change based on the value of that config
     *
     * @param Varien_Event_Observer $observer
     * @listen admin_system_config_changed_section_createsend_general
     * @throws Mage_Core_Exception if the API returns a bad result
     */
    public function checkConfig(Varien_Event_Observer $observer)
    {
        list($scope, $scopeId) = Mage::getSingleton('createsend/config_scope')
            ->_getScope($observer->getWebsite(), $observer->getStore());

        $request = Mage::app()->getRequest();
        $section = $request->getUserParam('section');
        $website = $request->getUserParam('website');
        $store   = $request->getUserParam('store');

        /** @var Mage_Core_Model_Config_Data $configData */
        $configData = Mage::getModel('adminhtml/config_data')
            ->setSection($section)
            ->setWebsite($website)
            ->setStore($store)
            ->load();

        // Do nothing unless there is a LIST ID configured for this scope.
        if (!isset($configData[Campaignmonitor_Createsend_Helper_Data::XML_PATH_LIST_ID])) {
            return;
        }

        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $listId = $helper->getScopedConfig(Campaignmonitor_Createsend_Helper_Data::XML_PATH_LIST_ID, $scope, $scopeId);

        // Create new list if requested
        /** @var Campaignmonitor_Createsend_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('createsend/api');

        if ($listId === $apiHelper::ID_CREATE_NEW_LIST
            && isset($configData[Campaignmonitor_Createsend_Helper_Data::XML_PATH_NEW_LIST_NAME])) {

            $newList = $configData[Campaignmonitor_Createsend_Helper_Data::XML_PATH_NEW_LIST_NAME];

            /** @var $api Campaignmonitor_Createsend_Model_Api */
            $api = Mage::getModel('createsend/api');

            /** @var Campaignmonitor_Createsend_Model_Config_Scope $configScope */
            $configScope = Mage::getSingleton('createsend/config_scope');
            $storeId = $configScope->getStoreIdFromScope($scope, $scopeId);

            $clientId = $helper->getApiClientId($storeId);
            $params = array(
                'Title' => $newList
            );
            $reply = $api->call(
                Zend_Http_Client::POST,
                "lists/${clientId}",
                $params,
                array(),
                $scope,
                $scopeId
            );

            if ($reply['success'] === false) {
                Mage::getSingleton('adminhtml/session')->addError(
                    sprintf($api::ERR_API_REQUEST, $reply['data']['Message'])
                );
            } else {
                // Successfully created new list, save the List ID
                $listId = $reply['data']['Message'];
                Mage::getConfig()->saveConfig(Campaignmonitor_Createsend_Helper_Data::XML_PATH_LIST_ID, $listId, $scope, $scopeId);
            }
        }

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        // Check if API config is complete and complete list set up
        if ($helper->isCompleteConfig($scope, $scopeId)) {
            /** @var Campaignmonitor_Createsend_Helper_Config $configHelper */
            $configHelper = Mage::helper('createsend/config');

            $flagClass = 'createsend/config_listFlag';
            $listFlagData = $configHelper->getFlagData($flagClass, $listId);
            if ($listFlagData === FALSE) {
                // Display message the first time list set up is complete
                Mage::getSingleton('adminhtml/session')->addSuccess(self::MSG_CONNECT_SUCCESS);
                Mage::getSingleton('adminhtml/session')->addSuccess(self::MSG_PRE_CREATED_CONFIG);

                $listFlagData = array(
                    'scope'             => $scope,
                    'scopeId'           => $scopeId,
                );

                $configHelper->addFlagData($flagClass, $listId, $listFlagData);
            }

            if ($helper->isSubscriberSynchronisationEnabled()) {
                // Set the flag to synchronise the list for the first time
                if (!array_key_exists(Campaignmonitor_Createsend_Helper_Config::INDEX_INITIAL_SYNC, $listFlagData)) {
                    $listFlagData[Campaignmonitor_Createsend_Helper_Config::INDEX_INITIAL_SYNC] = Campaignmonitor_Createsend_Helper_Config::FLAG_STATUS_NEW;
                    $configHelper->addFlagData($flagClass, $listId, $listFlagData);
                }
            }

            // Auto-create custom fields if not yet done for the List ID
            if (!array_key_exists(Campaignmonitor_Createsend_Helper_Config::INDEX_CUSTOM_FIELDS, $listFlagData)
                || $listFlagData[Campaignmonitor_Createsend_Helper_Config::INDEX_CUSTOM_FIELDS] === Campaignmonitor_Createsend_Helper_Config::FLAG_STATUS_NEW
            ) {
                $listFlagData[Campaignmonitor_Createsend_Helper_Config::INDEX_CUSTOM_FIELDS] = Campaignmonitor_Createsend_Helper_Config::FLAG_STATUS_PROCESSING;
                $configHelper->addFlagData($flagClass, $listId, $listFlagData);

                // Add default custom fields in configuration
                /** @var Campaignmonitor_Createsend_Model_Customer_Observer $observer */
                $observer = Mage::getModel('createsend/customer_observer');
                $observer->createAllCustomFields($scope, $scopeId);

                $listFlagData[Campaignmonitor_Createsend_Helper_Config::INDEX_CUSTOM_FIELDS] = Campaignmonitor_Createsend_Helper_Config::FLAG_STATUS_DONE;
                $configHelper->addFlagData($flagClass, $listId, $listFlagData);
            }

            // Auto-create example segments if not yet done for the List ID
            if (!array_key_exists(Campaignmonitor_Createsend_Helper_Config::INDEX_EXAMPLE_SEGMENTS, $listFlagData)
                || $listFlagData[Campaignmonitor_Createsend_Helper_Config::INDEX_EXAMPLE_SEGMENTS] === Campaignmonitor_Createsend_Helper_Config::FLAG_STATUS_NEW
            ) {
                $listFlagData[Campaignmonitor_Createsend_Helper_Config::INDEX_EXAMPLE_SEGMENTS] = Campaignmonitor_Createsend_Helper_Config::FLAG_STATUS_PROCESSING;
                $configHelper->addFlagData($flagClass, $listId, $listFlagData);

                $responses = $api->createExampleSegments($scope, $scopeId);

                foreach ($responses as $response) {
                    switch ($response['status']) {
                        case 'warning':
                            Mage::getSingleton('adminhtml/session')->addWarning($response['message']);
                            break;
                        case 'error':
                            Mage::getSingleton('adminhtml/session')->addError($response['message']);
                            break;
                    }
                }

                $listFlagData[Campaignmonitor_Createsend_Helper_Config::INDEX_EXAMPLE_SEGMENTS] = Campaignmonitor_Createsend_Helper_Config::FLAG_STATUS_DONE;
                $configHelper->addFlagData($flagClass, $listId, $listFlagData);
            }
        }

        $webhookId = $helper->getScopedConfig(Campaignmonitor_Createsend_Helper_Data::XML_PATH_WEBHOOK_ID, $scope, $scopeId);
        $webhookEnabled = $helper->isWebhookEnabled($store);

        // Create webhook if none found on this scope
        if ($webhookEnabled && $webhookId === false) {
            $result = $api->updateWebhooks($scope, $scopeId);
            if ($result['success'] === false) {
                Mage::throwException(
                    sprintf(Campaignmonitor_Createsend_Model_Api::ERR_CANNOT_UPDATE_WEBHOOK, $result['data']['Message'])
                );
            }
        } else {
            if (!$webhookEnabled && $webhookId !== false) {
                // There is a stored webhook ID for this scope, but webhooks are disabled,
                // so delete all registered webhooks for this scope
                $api->deleteAllWebhooks($listId, $scope, $scopeId);
            }
        }
    }

    /**
     * Responsible for handling the updating of users in Campaign Monitor before they are updated in Magento.
     *
     * @param Varien_Event_Observer $observer
     * @listen controller_action_predispatch_adminhtml_newsletter_subscriber_massUnsubscribe
     */
    public function massUnsubscribeUsers(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Controller_Request_Http $request */
        $subscribers = $observer->getControllerAction()->getRequest()->getPost('subscriber');
        if (!is_array($subscribers)) {
            Mage::getModel('adminhtml/session')
                ->addNotice('Could not unsubscribe subscribers from Createsend: No subscribers selected');
            return;
        }

        foreach ($subscribers as $subscriber) {
            /** @var Mage_Newsletter_Model_Subscriber $newsletter */
            $newsletter = Mage::getModel('newsletter/subscriber');
            $newsletter->load($subscriber);

            if ($newsletter->getCustomerId() != 0) {
                // Registered customer
                $scope = 'stores';
                $scopeId = $newsletter->getStoreId();
            } else {
                if ($newsletter->getStoreId()) {
                    $scope = 'stores';
                    $scopeId = $newsletter->getStoreId();
                } else {
                    // Guest user, use default scope (cannot get storeId from subscribeNewUser() observer)
                    $scope = 'default';
                    $scopeId = 0;
                }
            }

            /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
            $scopeModel = Mage::getModel('createsend/config_scope');
            $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

            Mage::getModel('createsend/api')->call(
                Zend_Http_Client::POST,
                "subscribers/" . Mage::helper('createsend')->getListId($storeId) . "/unsubscribe",
                array(
                    'EmailAddress' => $newsletter->getEmail()
                ),
                array(),
                $scope,
                $scopeId
            );
        }
    }

    /**
     * Subscribes a new user when given a request event
     *
     * @param Varien_Event_Observer $observer
     * @listen controller_action_predispatch_newsletter_subscriber_new
     */
    public function subscribeNewUser(Varien_Event_Observer $observer)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');
        $email = $observer->getControllerAction()->getRequest()->getPost('email');

        // If the parameter is missing, do nothing
        if (!$email) {
            $helper->log(self::ERR_NO_EMAIL_PARAMETER, Zend_Log::WARN);
            return;
        }

        $subscriberData = array(
            'Name' => self::SUBSCRIBER_DEFAULT_NAME,
            'EmailAddress' => $email,
            'Resubscribe' => true,
            'RestartSubscriptionBasedAutoresponders' => true
        );

        // $observer scope always returns default/0
        $scope = 'default';
        $scopeId = 0;

        /** @var Mage_Customer_Helper_Data $customerHelper */
        $customerHelper = Mage::helper('customer');
        if ($customerHelper->isLoggedIn()) {
            /** @var Mage_Customer_Model_Customer $customerModel */
            $customerModel = $customerHelper->getCustomer();
            $subscriberData['Name'] = $customerModel->getName();
            $subscriberData['CustomFields'] = Campaignmonitor_Createsend_Model_Customer_Observer::generateCustomFields($customerModel);

            // Use store scope only if customer is using the same email address.
            // Otherwise, use default/0 as above
            if (strcasecmp($email, $customerModel->getEmail()) === 0) {
                $scope = 'stores';
                // TODO: use current store id?
                $scopeId = $customerModel->getStoreId();
            }
        } else {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel("customer/customer");
            $customer->setWebsiteId(Mage::app()->getWebsite('admin')->getId());
            $customer->loadByEmail($email);
            $subscriberData['CustomFields'] = Campaignmonitor_Createsend_Model_Customer_Observer::generateCustomFields($customer);

            if ($customer->getId()) {
                // Do not subscribe to newsletter if customer is not logged in and a customer email is used.
                // Magento does not subscribe a registered customer to newsletter if customer is not logged in.
                $helper->log(sprintf(self::LOG_CUSTOMER_NOT_LOGGED_IN, $email), Zend_Log::WARN);

                return;
            } else {
                $scope = 'stores';
                $scopeId = Mage::app()->getStore()->getId();
            }
        }

        $helper->log(sprintf(self::LOG_ADD_SUBSCRIBER, $email));

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        $result = $api->call(
            Zend_Http_Client::POST,
            'subscribers/' . $helper->getListId($storeId),
            $subscriberData,
            array(),
            $scope,
            $scopeId
        );

        if (!$result['success']) {
            // We don't know what the API is going to return to the user. For safety we indicate to the user it was
            // an API error, but log the API error as a warning.
            Mage::getSingleton('core/session')->addError(
                sprintf(self::MSG_CANNOT_CREATE_SUBSCRIBER, $email)
            );

            // Log more detailed information for debugging
            $helper->log(
                sprintf(self::ERR_CANNOT_CREATE_SUBSCRIBER, $email, $result['data']['Message']),
                Zend_Log::WARN
            );
        }
    }
}
