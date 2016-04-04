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
 * Responsible for handling the webhooks posted by Campaign Monitor
 */
class Campaignmonitor_Createsend_WebhooksController extends Mage_Core_Controller_Front_Action
{
    const ERR_HOOK_LIST_DOESNT_MATCH = 'The list stored in config "%s" does not match the webhook list "%s"';

    const HTTP_OK                 = 200;
    const HTTP_BAD_REQUEST        = 400;
    const HTTP_METHOD_NOT_ALLOWED = 405;

    /**
     * Process the deactivate webhook
     *
     * Expects an array of the form:
     * [ListID] => fc6d0b8414e11d3a747ea73b05b23737
     * [Events] => Array
     *     [0] => Array
     *         [Type] => Deactivate
     *         [Date] => 2010-01-01
     *         [State] => Unsubscribed
     *         [EmailAddress] => test@example.org
     *         [Name] => Test Subscriber
     *         [CustomFields] => Array
     *             => Array
     *                 [Key] => website
     *                 [Value] => http://example.org
     *
     * @throws Zend_Controller_Response_Exception if the response code given is not in range
     */
    public function indexAction()
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $helper->log(
            sprintf('Webhook callback: ', implode(',', $this->getRequest()->getParams())),
            Zend_Log::DEBUG
        );

        // Do nothing if accessed outside of POST action
        if ($this->getRequest()->isPost() === false) {
            $this->getResponse()->setHttpResponseCode(self::HTTP_METHOD_NOT_ALLOWED);
            return;
        }

        try {
            $hookData = Zend_Json::decode($this->getRequest()->getRawBody());
        } catch (Zend_Json_Exception $e) {
            $helper->log('Error decoding webhook body: ' . $e->getMessage());
            $this->getResponse()->setHttpResponseCode(self::HTTP_BAD_REQUEST);
            return;
        }

        // Get scope/scopeId from the config for the LIST ID supplied by CM
        $configModel = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', Campaignmonitor_Createsend_Helper_Data::XML_PATH_LIST_ID)
            ->addFieldToFilter('value', $hookData['ListID'])
            ->addFieldToSelect('scope')
            ->addFieldToSelect('scope_id')
            ->setPageSize(1)
            ->getFirstItem();

        if (!$configModel) {
            $this->getResponse()->setHttpResponseCode(self::HTTP_BAD_REQUEST);
            return;
        }

        $scope = $configModel['scope'];
        $scopeId = $configModel['scope_id'];

        $storeId = Mage::getModel('createsend/config_scope')->getStoreIdFromScope($scope, $scopeId);

        /** @var Campaignmonitor_Createsend_Model_Newsletter $newsletter */
        $newsletter = Mage::getModel('createsend/newsletter');

        foreach ($hookData['Events'] as $event) {
            $email = $event['EmailAddress'];

            if ($event['Type'] === Campaignmonitor_Createsend_Model_Api::WEBHOOK_EVENT_SUBSCRIBE) {
                $newsletter->subscribe($email, $storeId);
            } elseif ($event['Type'] === Campaignmonitor_Createsend_Model_Api::WEBHOOK_EVENT_DEACTIVATE) {
                $newsletter->unsubscribe($email);
            } elseif ($event['Type'] === Campaignmonitor_Createsend_Model_Api::WEBHOOK_EVENT_UPDATE) {
                $oldEmail = $event['OldEmailAddress'];
                $state = $event['State'];

                if ($state === Campaignmonitor_Createsend_Model_Api::WEBHOOK_STATUS_ACTIVE) {
                    if ($oldEmail && $email !== $oldEmail) {
                        $newsletter->unsubscribe($oldEmail);
                    }

                    $newsletter->subscribe($email, $storeId);
                } elseif ($state === Campaignmonitor_Createsend_Model_Api::WEBHOOK_STATUS_UNSUBSCRIBED
                    || $state === Campaignmonitor_Createsend_Model_Api::WEBHOOK_STATUS_DELETED) {

                    if ($oldEmail && $email !== $oldEmail) {
                        $newsletter->unsubscribe($oldEmail);
                    }

                    $newsletter->unsubscribe($email);
                }
            }
        }

        $this->getResponse()->setHttpResponseCode(self::HTTP_OK);
    }
}