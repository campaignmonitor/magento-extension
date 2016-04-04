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
 * Campaign Monitor OAuth Permission Request Button
 *
 */

class Campaignmonitor_Createsend_Block_OauthPermissionRequest extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var Mage_Core_Model_Config_Data $configData */
        $configData = Mage::getSingleton('adminhtml/config_data');
        list($scope, $scopeId) = Mage::getSingleton('createsend/config_scope')
            ->_getScope($configData->getWebsite(), $configData->getStore());

        // Redirect admin to Campaign Monitor Application Approval Page

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');
        $url = $api->getOauthPermissionRequestUrl($scope, $scopeId);

        $this->setElement($element);

        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper("createsend");

        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($element->getLabel())
            //->setOnClick("setLocation('$url')")
            ->setOnClick("popWin('$url', '_blank')")
            ->setId($element->getId());

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        $clientId = $helper->getOAuthClientId($storeId);
        $clientSecret = $helper->getOAuthClientSecret($storeId);
        if (empty($clientId) || empty($clientSecret)) {
            // Hide if OAuth Client ID or Client Secret is blank
            $button->setDisabled('disabled');
        }

        return $button->toHtml();
    }
}