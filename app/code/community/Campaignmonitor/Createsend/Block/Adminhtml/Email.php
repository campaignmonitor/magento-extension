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

class Campaignmonitor_Createsend_Block_Adminhtml_Email extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected $_blockGroup = 'createsend';
    protected $_controller = 'adminhtml_email';

    public function __construct()
    {
        parent::__construct();
        $this->_removeButton('add');
    }

    /**
     * @return string
     */
    public function getHeaderText()
    {
        return $this->__('Campaign Monitor - Email Tracking');
    }
}