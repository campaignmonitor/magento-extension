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
 * Bug fix for Magento newsletter's change_status_at field non-update bug.
 */
class Campaignmonitor_Createsend_Model_Newsletter_Observer
{
    /**
     * Updates the change_status_at field. Fixes a bug in Magento where the field is not updated
     * because the field's auto update "ON UPDATE CURRENT_TIMESTAMP" was omitted in recent versions.
     *
     * @param Varien_Event_Observer $observer
     * @listen newsletter_subscriber_save_before
     */
    public function setChangeStatusAt(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getSubscriber();
        $subscriber['change_status_at'] = date("Y-m-d H:i:s", time());
    }
}