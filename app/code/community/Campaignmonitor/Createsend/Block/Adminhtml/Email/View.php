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

class Campaignmonitor_Createsend_Block_Adminhtml_Email_View extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected $_objectId    = 'id';
    protected $_blockGroup  = 'createsend';
    protected $_controller  = 'adminhtml_email';
    protected $_mode        = 'view';

    const HEADER_MESSAGE_ID      = 'Message ID: %s';

    public function __construct()
    {
        parent::__construct();

        if ($this->isResendable()) {
            $this->_updateButton('save', 'label', Mage::helper('createsend')->__('Resend'));
        } else {
            $this->_removeButton('save');
        }

        $this->_removeButton('reset');
    }

    public function getHeaderText()
    {
        $message = $this->getMessage();

        return sprintf(Mage::helper('createsend')->__(self::HEADER_MESSAGE_ID), htmlentities($message['MessageID']));
    }

    public function getBackUrl()
    {
        $request = Mage::app()->getRequest();
        $customerId = $request->getUserParam('customer_id');
        if ($customerId) {
            return $this->getUrl(
                'adminhtml/customer/edit',
                array(
                    'id'        => $customerId,
                    '_query'    => array('active_tab' => 'createsend_email_grid')
                )
            );
        } else {
            return $this->getUrl('adminhtml/createsend_email/index');
        }
    }

    /**
     * Returns the email message object from registry. Message is of the form of an
     * array as returned by Campaign Monitor API.
     *
     * @link https://www.campaignmonitor.com/api/transactional/#message_details
     *
     * @return array
     */
    public function getMessage()
    {
        return Mage::registry('createsend_email');
    }

    /**
     * Returns true if the message can be resent.
     *
     * @return bool
     */
    public function isResendable()
    {
        $message = $this->getMessage();

        $key = 'CanBeResent';
        if (array_key_exists($key, $message)) {
            if (is_bool($message[$key])) {
                return $message[$key];
            } else {
                return (mb_strtoupper(trim($message[$key])) === mb_strtoupper("true")) ? TRUE : FALSE;
            }
        } else {
            // Cannot be resent by default
            return false;
        }
    }
}