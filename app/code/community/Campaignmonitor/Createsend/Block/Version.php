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
class Campaignmonitor_Createsend_Block_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->getExtensionVersion();
    }

    public function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Campaignmonitor_Createsend->version;
    }
}