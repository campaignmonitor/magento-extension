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

class Campaignmonitor_Createsend_Model_Config_Backend_Apikey extends Mage_Core_Model_Config_Data
{
    const ERR_INVALID_API_KEY       = 'Invalid API Key: %s';
    const MSG_VALID_API_KEY         = 'Valid API Key.';
    /**
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterSave()
    {
        $request = Mage::app()->getRequest();
        $website = $request->getUserParam('website');
        $store   = $request->getUserParam('store');
        list($scope, $scopeId) = Mage::getSingleton('createsend/config_scope')->_getScope($website, $store);

        /** @var Campaignmonitor_Createsend_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('createsend/api');

        // Test API Key by getting the list of clients
        $reply = $apiHelper->testApiKey($scope, $scopeId);

        if ($reply['success'] === false) {
            Mage::getSingleton('adminhtml/session')->addError(
                sprintf(self::ERR_INVALID_API_KEY, $reply['data']['Message'])
            );
        } else {
            // Display success message only when value is changed
            if ($this->isValueChanged()) {
                Mage::getSingleton('adminhtml/session')->addSuccess(sprintf(self::MSG_VALID_API_KEY));
            }
        }

        return parent::_afterSave();
    }
}