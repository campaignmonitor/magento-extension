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

class Campaignmonitor_Createsend_Model_Email_Cron extends Campaignmonitor_Createsend_Model_Cron
{
    const ERR_API_ERROR         = 'API Error: %s';
    const MAX_EMAIL_PER_REQUEST = 200;
    const MAX_REQUEST_LOOPS     = 100;              // To prevent endless loops in the email retrieval process
    const DATE_MIN              = '1972-01-01 00:00:00';

    const LOG_DELETING_EMAILS   = 'Deleting email sent before %1$s for scope/scopeId (%2$s/%3$s)...';
    const LOG_ROWS_DELETED      = '%d email rows deleted.';

    /**
     * Performs email headers download for all scopes w/ non-inherited List ID
     */
    public function runJob()
    {
        $this->iterateScopes(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_LIST_ID,
            'isTransactionalEmailsEnabled',
            array(
                array(
                    'class'     => $this,
                    'method'    => '_downloadEmailFromCM',
                ),
                array(
                    'class'     => $this,
                    'method'    => '_performEmailTableMaintenance',
                ),
            )
        );
    }

    /**
     * Downloads email headers for scope/scopeId
     *
     * @param string $listId Unused in this function because List ID is not required for sending transactional emails
     * @param string $scope The configuration Scope for which to perform this action
     * @param int $scopeId The configuration Scope ID for which to perform this action
     */
    protected function _downloadEmailFromCM($listId, $scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getSingleton('createsend/api');

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        /** @var array $params */
        $params =  array(
            'clientID'      => $helper->getClientId($storeId),
            'count'         => self::MAX_EMAIL_PER_REQUEST,
        );

        // Get last message id for this scope/scopeId from the database
        /** @var array $row */
        $row = Mage::getModel('createsend/email')->getCollection()
            ->addFieldToFilter('scope', $scope)
            ->addFieldToFilter('scope_id', $scopeId)
            ->setOrder('sent_at', 'DESC')
            ->setPageSize(1)
            ->getFirstItem();

        $lastMessageId = null;
        $lastMessageDate = self::DATE_MIN;
        if ($row) {
            $lastMessageId = $row['message_id'];
            $lastMessageDate = $row['sent_at'];

            $params['sentAfterID'] = $lastMessageId;
        }

        $loopCount = 0;
        do {
            // Get "Message timeline" from CM
            $result = $api->call(
                Zend_Http_Client::GET,
                "transactional/messages",
                array(),
                $params,
                $scope,
                $scopeId
            );

            $count = 0;
            if ($result['success'] !== false) {
                foreach ($result['data'] as $message) {
                    $count++;

                    /** @var Campaignmonitor_Createsend_Model_Email $email */
                    $email = Mage::getModel('createsend/email');
                    $email->setMessageId($message['MessageID']);
                    $email->setStatus($message['Status']);
                    $email->setSentAt($message['SentAt']);
                    $email->setRecipient(iconv_mime_decode($message['Recipient']));
                    $email->setSender($message['From']);
                    $email->setSubject(iconv_mime_decode($message['Subject']));
                    $email->setTotalOpens($message['TotalOpens']);
                    $email->setTotalClicks($message['TotalClicks']);
                    $email->setCanBeResent($message['CanBeResent']);
                    $email->setScope($scope);
                    $email->setScopeId($scopeId);
                    $email->save();

                    // Compare dates, don't assume message dates are in order
                    if ($message['SentAt'] >= $lastMessageDate) {
                        $lastMessageId = $message['MessageID'];
                        $lastMessageDate = $message['SentAt'];
                    }
                }

                $params['sentAfterID'] = $lastMessageId;
            } else {
                $helper->log(sprintf(self::ERR_API_ERROR, var_export($result, true)), Zend_Log::ERR);
            }

        } while (($count >= self::MAX_EMAIL_PER_REQUEST) && (++$loopCount < self::MAX_REQUEST_LOOPS));
    }

    /**
     * Deletes all email with sent_at earlier than the email retention days configuration
     * for the given scope/scopeId.
     *
     * @param string $listId Unused in this function because List ID is not required for sending transactional emails
     * @param string $scope The configuration Scope for which to perform this action
     * @param int $scopeId The configuration Scope ID for which to perform this action
     */
    protected function _performEmailTableMaintenance($listId, $scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        $retentionDays = $helper->getEmailRetentionDays($storeId);

        $daysAgo = Mage::getModel('core/date')->timestamp(strtotime(sprintf('%d days ago', $retentionDays)));
        $daysAgoStr = date('Y-m-d', $daysAgo);

        $helper->log(
            sprintf(self::LOG_DELETING_EMAILS, $daysAgoStr, $scope, $scopeId),
            Zend_Log::DEBUG
        );

        /** @var Varien_Data_Collection_Db $collection */
        $collection = Mage::getModel('createsend/email')->getCollection()
            ->addFieldToFilter('scope', $scope)
            ->addFieldToFilter('scope_id', $scopeId)
            ->addFieldToFilter('sent_at', array('lt' => $daysAgoStr))
            ->load();

        $count = 0;
        /** @var Campaignmonitor_Createsend_Model_Email $email */
        foreach ($collection as $email) {
            $email->delete();
            $count++;
        }

        $helper->log(sprintf(self::LOG_ROWS_DELETED, $count), Zend_Log::DEBUG);
    }
}
