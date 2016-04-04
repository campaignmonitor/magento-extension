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

class Campaignmonitor_Createsend_Model_List_Cron extends Campaignmonitor_Createsend_Model_Cron
{
    const SYNC_INCLUSION_PERIOD     = 'yesterday';      // Relative date/time format
    const ERR_API_ERROR             = 'API Error: %s';

    /**
     * Performs list subscribers synchronisation for all scopes w/ non-inherited List ID
     */
    public function runJob()
    {
        $this->iterateScopes(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_LIST_ID,
            'isSubscriberSynchronisationEnabled',
            array(
                array(
                    'class'     => $this,
                    'method'    => '_synchroniseFromCm',
                ),
            )
        );

        $this->synchroniseFromMagento();
    }

    /**
     * Synchronises list subscribers using Campaign Monitor subscribers as base list.
     *
     * @link https://www.campaignmonitor.com/api/lists/#active_subscribers
     *
     * @param string $listId The ID of the Campaign Monitor list to synchronise
     * @param string $scope
     * @param int $scopeId
     */
    public function _synchroniseFromCm($listId, $scope, $scopeId)
    {
        $dateTimestamp = Mage::getModel('core/date')->timestamp(strtotime(self::SYNC_INCLUSION_PERIOD));
        $yesterday = date('Y-m-d', $dateTimestamp);

        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getSingleton('createsend/api');

        // Get List of Active Subscribers
        $result = $api->call(
            Zend_Http_Client::GET,
            "lists/{$listId}/active",
            array(),
            array('date' => $yesterday),
            $scope,
            $scopeId
        );

        if ($result['success'] !== false) {
            foreach ($result['data']['Results'] as $subscriber) {
                $email = $subscriber['EmailAddress'];

                /** @var Mage_Newsletter_Model_Subscriber $mageNewsletter */
                $mageNewsletter = Mage::getModel('newsletter/subscriber');
                $mageNewsletter->loadByEmail($email);

                if (!$mageNewsletter->isSubscribed() || $mageNewsletter->getId() === null) {
                    $mageTimestamp = null;
                    if ($mageNewsletter->getId() !== null) {
                        $mageTime = Mage::getModel('core/date')->timestamp($mageNewsletter->getChangeStatusAt());
                        $mageTimestamp = date('Y-m-d H:i:s', $mageTime);
                    }

                    // CM in local time
                    $cmTimestamp = null;
                    if (isset($subscriber['Date'])) {
                        $cmTimestamp = $subscriber['Date'];
                    }

                    $this->_applyResolutionMethod(
                        Campaignmonitor_Createsend_Model_Config_SubscriptionSources::SOURCE_CAMPAIGN_MONITOR,
                        $email, $mageTimestamp, $cmTimestamp, $scope, $scopeId
                    );
                }
            }
        } else {
            $helper->log(sprintf(self::ERR_API_ERROR, $result['data']['Message']), Zend_Log::ERR);
        }
    }

    /**
     * Synchronises list subscribers using Magento subscribers from all stores as the base list.
     *
     */
    public function synchroniseFromMagento()
    {
        $stores = Mage::app()->getStores(true);
        foreach ($stores as $storeId => $store) {
            $this->_synchroniseFromMagento($storeId);
        }
    }

    /**
     * Synchronises list subscribers using Magento newsletter subscribers from the particular store as the base list.
     *
     * @param int $storeId
     */
    public function _synchroniseFromMagento($storeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $listId = $helper->getListId($storeId);
        $scope = 'stores';
        $scopeId = $storeId;

        /* @var Mage_Newsletter_Model_Subscriber $subscribers */
        $mageNewsletter = Mage::getModel('newsletter/subscriber');

        $collection = $mageNewsletter->getCollection()
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('subscriber_status', Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);

        foreach ($collection as $subscriber) {
            $email = $subscriber->getEmail();

            /** @var Campaignmonitor_Createsend_Model_Api $api */
            $api = Mage::getSingleton('createsend/api');

            // Check CM subscriber status
            $result = $api->call(
                Zend_Http_Client::GET,
                "subscribers/{$listId}",
                array(),
                array('email' => $email),
                $scope,
                $scopeId
            );

            if ($result['success'] || $result['data']['Code'] == $api::CODE_SUBSCRIBER_NOT_IN_LIST) {
                if (isset($result['data']['State'])) {
                    $subscriptionState = $result['data']['State'];
                } else {
                    $subscriptionState = $api::SUBSCRIBER_STATUS_DELETED;
                }

                if ($subscriptionState !== $api::SUBSCRIBER_STATUS_ACTIVE) {
                    // Convert to local time
                    $mageTime = Mage::getModel('core/date')->timestamp($subscriber->getChangeStatusAt());
                    $mageTimeStamp = date('Y-m-d H:i:s', $mageTime);

                    // CM in local time
                    $cmTimestamp = null;
                    if (isset($result['data']['Date'])) {
                        $cmTimestamp = $result['data']['Date'];
                    }

                    $this->_applyResolutionMethod(
                        Campaignmonitor_Createsend_Model_Config_SubscriptionSources::SOURCE_MAGENTO,
                        $email, $mageTimeStamp, $cmTimestamp, $scope, $scopeId
                    );
                }
            }
        }
    }

    /**
     * Performs conflict resolution based on source list and resolution method of scope/scopeId
     *
     * @param string $source The source where the email address is subscribed
     * @param string $email The email address to be subscribed/unsubscribed
     * @param string $mageTimestamp Date when magento newsletter subscription was last updated, format: 'Y-m-d H:i:s'
     * @param string $cmTimestamp Date when CM list subscription was last updated, format: 'Y-m-d H:i:s'
     * @param string $scope
     * @param int $scopeId
     */
    protected function _applyResolutionMethod($source, $email, $mageTimestamp, $cmTimestamp, $scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $helper->log(
            sprintf('RESOLVE (%s): From(%s); Magento(%s); CM(%s)', $email, $source, $mageTimestamp, $cmTimestamp)
        );

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        $resolutionMethod = $helper->getSubscriberSynchronisationResolutionMethod($storeId);

        switch ($resolutionMethod) {
            case Campaignmonitor_Createsend_Model_Config_ConflictResolutionMethod::RESOLUTION_METHOD_TIMESTAMP:
                if ($mageTimestamp === null) {
                    $this->_preferCM($source, $email, $storeId);
                } elseif ($cmTimestamp === null) {
                    $this->_preferMagento($source, $email, $scope, $scopeId);
                } elseif ($mageTimestamp < $cmTimestamp) {
                    $this->_preferCM($source, $email, $storeId);
                } else {
                    $this->_preferMagento($source, $email, $scope, $scopeId);
                }

                break;

            case Campaignmonitor_Createsend_Model_Config_ConflictResolutionMethod::RESOLUTION_METHOD_SOURCE:
                $preferredSource = $helper->getSubscriberSynchronisationPreferredSource($storeId);

                switch ($preferredSource) {
                    case Campaignmonitor_Createsend_Model_Config_SubscriptionSources::SOURCE_MAGENTO:
                        $this->_preferMagento($source, $email, $scope, $scopeId);
                        break;

                    case Campaignmonitor_Createsend_Model_Config_SubscriptionSources::SOURCE_CAMPAIGN_MONITOR:
                    default: // If not set, use CM as preferred source
                        $this->_preferCM($source, $email, $storeId);
                        break;
                }
                break;
        }
    }

    /**
     * Uses the CM subscription status to update Magento newsletter subscription
     *
     * @param string $source The source where the email address is subscribed
     * @param string $email The email address to be subscribed/unsubscribed
     * @param int $storeId Store ID of the Magento newsletter object
     */
    protected function _preferCM($source, $email, $storeId)
    {
        /** @var Campaignmonitor_Createsend_Model_Newsletter $newsletter */
        $newsletter = Mage::getModel('createsend/newsletter');

        if ($source === Campaignmonitor_Createsend_Model_Config_SubscriptionSources::SOURCE_CAMPAIGN_MONITOR) {
            $newsletter->subscribe($email, $storeId);
        } else {
            $newsletter->unsubscribe($email);
        }
    }

    /**
     * Uses the Magento newsletter subscription status to update CM subscription
     *
     * @param string $source The source where the email address is subscribed
     * @param string $email The email address to be subscribed/unsubscribed
     * @param string $scope
     * @param int $scopeId
     */
    protected function _preferMagento($source, $email, $scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Model_Api $api  */
        $api = Mage::getModel('createsend/api');

        if ($source === Campaignmonitor_Createsend_Model_Config_SubscriptionSources::SOURCE_CAMPAIGN_MONITOR) {
            $api->unsubscribe($email, $scope, $scopeId);
        } else {
            $api->subscribe($email, $scope, $scopeId);
        }
    }
}