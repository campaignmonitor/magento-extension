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
 * Campaign Monitor OAuth Redirect URL information.
 *
 */
class Campaignmonitor_Createsend_Block_OauthRedirectUrl extends Mage_Adminhtml_Block_System_Config_Form_Field
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

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');
        $url = $api->getOauthRedirectUri($scope, $scopeId);

        $element->setValue($url);

        // Add a hidden input field to get <depends> working
        $html = $element->getAfterElementHtml();
        $element->setAfterElementHtml($html . sprintf('<input id="%s" type="hidden" value=""/>', $element->getId()));

        return parent::_getElementHtml($element);
    }
}