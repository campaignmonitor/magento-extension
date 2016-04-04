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

class Campaignmonitor_Createsend_Adminhtml_Createsend_EmailController extends Mage_Adminhtml_Controller_Action
{
    const ERR_UNABLE_TO_RESEND_EMAIL        = 'Unable to resend email: %s';
    const ERR_EMAIL_NOT_FOUND               = 'Email not found.';
    const ERR_API_ERROR                     = 'API Error: %s';
    const ERR_EMAIL_CANNOT_BE_RESENT        = 'Email cannot be resent';
    const LOG_EMAIL_RESENT                  = 'Email resend status: %s';

    const PATH_EMAIL_VIEW                   = 'adminhtml/createsend_email/view';
    const PATH_EMAIL_INDEX                  = 'adminhtml/createsend_email/index';

    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('system');
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function viewAction()
    {
        $emailId = $this->getRequest()->getParam('email_id');
        $display = $this->getRequest()->getParam('display');

        /** @var Campaignmonitor_Createsend_Model_Email $email */
        $email = Mage::getModel('createsend/email')->load($emailId);

        $messageId = $email->getMessageId();

        if (empty($messageId)) {
            Mage::getSingleton('adminhtml/session')->addError(sprintf(self::ERR_EMAIL_NOT_FOUND));
            return $this->_redirectReferer();
        }

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        $scope = $email->getScope();
        $scopeId = $email->getScopeId();

        $reply = $api->call(
            Zend_Http_Client::GET,
            "transactional/messages/${messageId}",
            array(),
            array('statistics' => 'true'),
            $scope,
            $scopeId
        );

        if (!$reply['success']) {
            Mage::getSingleton('adminhtml/session')->addError(sprintf(self::ERR_API_ERROR, $reply['data']['Message']));
            return $this->_redirectReferer();
        }

        $reply['data']['email_id'] = $emailId;

        if ($display === 'body') {
            if (isset($reply['data']['Message']['Body']['Html'])) {
                print $reply['data']['Message']['Body']['Html'];
            } else {
                print '';
            }
        } else {
            Mage::register('createsend_email', $reply['data']);

            $this->loadLayout();

            $request = Mage::app()->getRequest();
            $customerId = $request->getUserParam('customer_id');
            if ($customerId) {
                $this->_setActiveMenu('customer');
            } else {
                $this->_setActiveMenu('system');
            }

            return $this->renderLayout();
        }
    }

    public function resendAction()
    {
        $emailId = $this->getRequest()->getParam('email_id');

        /** @var Campaignmonitor_Createsend_Model_Email $email */
        $email = Mage::getModel('createsend/email')->load($emailId);

        $messageId = $email->getMessageId();

        if (empty($messageId)) {
            Mage::getSingleton('adminhtml/session')->addError(sprintf(self::ERR_EMAIL_NOT_FOUND));
            return $this->_redirect(self::PATH_EMAIL_VIEW, array('email_id' => $emailId));
        }

        if (!$email->getCanBeResent()) {
            Mage::getSingleton('adminhtml/session')->addError(sprintf(self::ERR_EMAIL_CANNOT_BE_RESENT));
            return $this->_redirect(self::PATH_EMAIL_VIEW, array('email_id' => $emailId));
        }

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        $scope = $email->getScope();
        $scopeId = $email->getScopeId();

        $reply = $api->call(
            Zend_Http_Client::POST,
            "transactional/messages/${messageId}/resend",
            array(),
            array(),
            $scope,
            $scopeId
        );

        if ($reply['success'] !== false) {
            Mage::getSingleton('adminhtml/session')
                ->addSuccess(sprintf(self::LOG_EMAIL_RESENT, $reply['data']['Status']));
        } else {
            Mage::getSingleton('adminhtml/session')
                ->addError(sprintf(self::ERR_UNABLE_TO_RESEND_EMAIL, $reply['data']['Message']));
        }

        return $this->_redirect(self::PATH_EMAIL_VIEW, array('email_id' => $emailId));
    }
}