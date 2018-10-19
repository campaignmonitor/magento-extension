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
 * Extends the System Config Switcher to remove unwanted text.
 */
class Campaignmonitor_Createsend_Block_Adminhtml_Switcher extends Mage_Adminhtml_Block_System_Config_Switcher
{
    protected function _prepareLayout()
    {
        $this->setTemplate('campaignmonitor/createsend/switcher.phtml');
        return Mage_Adminhtml_Block_Template::_prepareLayout();
    }
}