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

class Campaignmonitor_Createsend_Model_Config_Backend_Listid extends Mage_Core_Model_Config_Data
{
    /**
     * If the List ID value is changed, delete all webhooks associated with the old List ID
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterSave()
    {
        if ($this->isValueChanged()) {
            $oldListId = $this->getOldValue();

            $request = Mage::app()->getRequest();
            $website = $request->getUserParam('website');
            $store   = $request->getUserParam('store');
            list($scope, $scopeId) = Mage::getSingleton('createsend/config_scope')->_getScope($website, $store);

            /** @var Campaignmonitor_Createsend_Model_Api $api */
            $api = Mage::getModel('createsend/api');

            $api->deleteAllWebhooks($oldListId, $scope, $scopeId);
        }

        return parent::_afterSave();
    }
}