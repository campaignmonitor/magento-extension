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

abstract class Campaignmonitor_Createsend_Block_Adminhtml_Email_View_Abstract
    extends Mage_Adminhtml_Block_Widget
{
    /**
     * Retrieve email details, an array returned by Campaign Monitor API
     * @link https://www.campaignmonitor.com/api/transactional/#message_details
     *
     * @return array
     */
    public function getEmail()
    {
        return Mage::registry('createsend_email');
    }
}
