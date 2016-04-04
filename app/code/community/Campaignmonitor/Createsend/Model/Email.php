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

class Campaignmonitor_Createsend_Model_Email extends Mage_Core_Model_Abstract
{
    const STATUS_SENT           = 'Sent';
    const STATUS_ACCEPTED       = 'Accepted';
    const STATUS_DELIVERED      = 'Delivered';
    const STATUS_BOUNCED        = 'Bounced';
    const STATUS_SPAM           = 'Spam';

    static $statusCodes = array(
        self::STATUS_SENT       => 'Sent',
        self::STATUS_ACCEPTED   => 'Accepted',
        self::STATUS_DELIVERED  => 'Delivered',
        self::STATUS_BOUNCED    => 'Bounced',
        self::STATUS_SPAM       => 'Spam'
    );

    protected function _construct()
    {
        $this->_init('createsend/email');
    }
}
