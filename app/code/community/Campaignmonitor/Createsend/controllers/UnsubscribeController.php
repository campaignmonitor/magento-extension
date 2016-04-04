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

class Campaignmonitor_Createsend_UnsubscribeController extends Mage_Core_Controller_Front_Action
{
//
//    /**
//     * Responsible for unsubscribing users already unsubscribed by Campaign Monitor.
//     *
//     * @deprecated in 1.0.0 in favour of Webhooks. See Campaignmonitor_Createsend_WebhooksController.
//     */
//
//    public function indexAction()
//    {
//        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
//        $helper = Mage::helper("createsend");
//        $email = $this->getRequest()->getQuery('email');
//
//        // don't do anything if we didn't get the email parameter
//        if (!$email) {
//            return $this->_redirect('customer/account/');
//        }
//        /** @var Campaignmonitor_Createsend_Model_Api $api */
//        $api = Mage::getModel('createsend/api');
//
//        // Check that the email address actually is unsubscribed in Campaign Monitor
//        $result = $api->call(
//            Zend_Http_Client::GET,
//            'subscribers/' . $helper->getListId(),
//            null,
//            array(
//                'email' => $email
//            )
//        );
//
//        if ($result['success'] === false) {
//            return $this->_redirect('customer/account');
//        }
//
//        // If customer is unsubscribed in Campaign Monitor mark as such in Magento
//        if (in_array($result['Data']['State'], array('Unsubscribed', 'Deleted'))) {
//            $helper->log("Unsubscribing $email");
//            Mage::getModel('newsletter/subscriber')
//                ->loadByEmail($email)
//                ->unsubscribe();
//            Mage::getSingleton('customer/session')->addSuccess($this->__('You were successfully unsubscribed'));
//        } else {
//            Mage::getSingleton('customer/session')->addWarning($this->__('Please unsubscribe from Campaign Monitor first'));
//        }
//
//        return $this->_redirect('customer/account/');
//    }
}
