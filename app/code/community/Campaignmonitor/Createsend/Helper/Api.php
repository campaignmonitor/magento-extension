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

class Campaignmonitor_Createsend_Helper_Api extends Mage_Core_Helper_Abstract
{
    const MAX_CM_SUBSCRIBER_IMPORT      = 1000;
    const LABEL_SELECT_CLIENT           = 'Select Client...';
    const LABEL_CREATE_NEW_LIST         = 'Create a new list in Campaign Monitor';
    const LABEL_ENTER_YOUR_API_KEY      = 'Enter your API Key';
    const ID_CREATE_NEW_LIST            = 'NEW_LIST';

    /**
     * Gets all the Campaign Monitor clients for the given scope/scopeId for use in an HTML select.
     * The first option will be a 'Select Client...' prompt.
     *
     * On API Error, returns a single element array with key 'value' => 'error'
     *
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    public function getClients($scope, $scopeId)
    {
        /** @var $api Campaignmonitor_Createsend_Model_Api */
        $api = Mage::getModel('createsend/api');

        $reply = $api->call(
            Zend_Http_Client::GET,
            "clients",
            array(),
            array(),
            $scope,
            $scopeId
        );

        $clients = array();
        if ($reply['success'] === false) {
            Mage::helper('createsend')->log($reply);

            $clients[] = array(
                'value'     => 'error',
                'label'     => self::LABEL_ENTER_YOUR_API_KEY,
                'message'   => sprintf($api::ERR_API_REQUEST, $reply['data']['Message'])
            );
        } else {
            /** @var $helper Campaignmonitor_Createsend_Helper_Data */
            $helper = Mage::helper('createsend');

            $clients[] = array(
                'value' => '',
                'label' => $helper->__(self::LABEL_SELECT_CLIENT)
            );

            foreach ($reply['data'] as $client) {
                $clients[] = array(
                    'value'  => $client['ClientID'],
                    'label'  => $client['Name']
                );
            }
        }

        return $clients;
    }

    /**
     * Gets all the Campaign Monitor subscriber lists for the given clientId
     * using credentials from given scope/scopeId for use in an HTML select.
     * The last option will be a 'Create a new list' option
     *
     * On API Error, returns a single element array with key 'value' => 'error'
     *
     * @param string $clientId
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    public function getLists($clientId, $scope, $scopeId)
    {
        /** @var $api Campaignmonitor_Createsend_Model_Api */
        $api = Mage::getModel('createsend/api');

        $reply = $api->call(
            Zend_Http_Client::GET,
            "clients/${clientId}/lists",
            array(),
            array(),
            $scope,
            $scopeId
        );

        $lists = array();
        if ($reply['success'] === false) {
            Mage::helper('createsend')->log($reply);

            $lists[] = array(
                'value'     => 'error',
                'label'     => self::LABEL_ENTER_YOUR_API_KEY,
                'message'   => sprintf($api::ERR_API_REQUEST, $reply['data']['Message'])
            );
        } else {
            /** @var $helper Campaignmonitor_Createsend_Helper_Data */
            $helper = Mage::helper('createsend');

            foreach ($reply['data'] as $client) {
                $lists[] = array(
                    'value'  => $client['ListID'],
                    'label'  => $client['Name']
                );
            }

            $lists[] = array(
                'value' => self::ID_CREATE_NEW_LIST,
                'label' => $helper->__(self::LABEL_CREATE_NEW_LIST)
            );
        }

        return $lists;
    }

    /**
     * Tests the API Key by getting the list of clients.
     *
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    public function testApiKey($scope, $scopeId)
    {
        /** @var $api Campaignmonitor_Createsend_Model_Api */
        $api = Mage::getModel('createsend/api');

        $reply = $api->call(
            Zend_Http_Client::GET,
            "clients",
            array(),
            array(),
            $scope,
            $scopeId
        );

        return $reply;
    }

    /**
     * Lists all Magento subscribers and returns the list in an array compatible with
     * the Campaign Monitor API.
     *
     * @param int $storeId
     * @return array
     */
    public function getSubscribers($storeId)
    {
        $listData = array();

        /** @var Mage_Newsletter_Model_Subscriber $subscribers */
        $subscribers = Mage::getModel('newsletter/subscriber');

        $collection = $subscribers->getCollection()
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('subscriber_status', Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);

        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        foreach ($collection as $subscriber) {
            $email = $subscriber->getSubscriberEmail();

            $subscriberData['Name'] = "";
            $subscriberData['CustomFields'] = array();
            $subscriberData['EmailAddress'] = $email;

            $websiteId = Mage::app()->getStore($subscriber->getStoreId())->getWebsiteId();

            /* @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel("customer/customer")
                ->setWebsiteId($websiteId)
                ->loadByEmail($email);

            if ($customer->getId()) {
                $subscriberData['Name'] = $customer->getName();

                $subscriberData['CustomFields'] =
                    Campaignmonitor_Createsend_Model_Customer_Observer::generateCustomFields($customer);

            }

            $listData[] = $subscriberData;
        }

        return $listData;
    }

    /**
     * Performs an initial full subscriber sync from Magento to Campaign Monitor
     * for a particular store. The check for already synchronized list should be
     * done by the caller.
     *
     * @param $storeId
     * @return array|bool|null
     */
    function performFullSync($storeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $listId = $helper->getListId($storeId);
        $scope = 'stores';
        $scopeId = $storeId;

        /** @var Campaignmonitor_Createsend_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('createsend/api');

        $listData = $apiHelper->getSubscribers($storeId);

        /** @var $api Campaignmonitor_Createsend_Model_Api */
        $api = Mage::getModel('createsend/api');

        $index = 0;
        do {
            $partialData = array_slice($listData, $index * self::MAX_CM_SUBSCRIBER_IMPORT, self::MAX_CM_SUBSCRIBER_IMPORT);
            $reply = $api->call(
                Zend_Http_Client::POST,
                "subscribers/{$listId}/import",
                array(
                    'Subscribers'                            => $partialData,
                    'Resubscribe'                            => false,
                    'QueueSubscriptionBasedAutoResponders'   => false,
                    'RestartSubscriptionBasedAutoresponders' => true
                ),
                array(),
                $scope,
                $scopeId
            );
        } while (
            $reply['success'] !== false
            &&
            count($listData) > (($index++ * self::MAX_CM_SUBSCRIBER_IMPORT) + self::MAX_CM_SUBSCRIBER_IMPORT)
        );

        return $reply;
    }
}