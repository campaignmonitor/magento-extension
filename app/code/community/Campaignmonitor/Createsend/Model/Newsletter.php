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

class Campaignmonitor_Createsend_Model_Newsletter
{
    const ERR_NO_EMAIL_FOUND         = 'The email "%s" appears to have no matching entries in Magento';
    const ERR_USER_SUBSCRIBED        = 'The user "%s" is already subscribed in Magento';
    const ERR_USER_UNSUBSCRIBED      = 'The user "%s" has already unsubscribed in Magento';

    const LOG_SUBSCRIBED_USER   = 'Successfully subscribed customer "%s": %s';
    const LOG_UNSUBSCRIBED_USER = 'Successfully unsubscribed customer "%s": %s';

    /**
     * Subscribes an email address to the Magento newsletter.
     *
     * @param string $email The email address to subscribe to the Magento newsletter
     * @param int $storeId The store ID to set for the newsletter subscription
     * @throws Exception if newsletter object cannot be saved
     */
    public function subscribe($email, $storeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Mage_Newsletter_Model_Subscriber $newsletters */
        $newsletters = Mage::getModel('newsletter/subscriber');
        $newsletters->loadByEmail($email);

        if ($newsletters->getId() === null) {
            // Create new subscriber, without sending confirmation email
            Mage::getModel('newsletter/subscriber')->setImportMode(true)->subscribe($email);
            $newsletters->loadByEmail($email);

            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel("customer/customer")
                ->setWebsiteId($websiteId)
                ->loadByEmail($email);

            if ($customer->getId()) {
                $newsletters->setCustomerId($customer->getId());
            }

            $newsletters->setStoreId($storeId);

            $newsletters->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
            $newsletters->save();

            $helper->log(
                sprintf(self::LOG_SUBSCRIBED_USER, $newsletters->getCustomerId(), $newsletters->getEmail()),
                Zend_Log::DEBUG
            );
        } else {
            if ($newsletters->isSubscribed() === false) {
                $newsletters->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
                $newsletters->save();

                $helper->log(
                    sprintf(self::LOG_SUBSCRIBED_USER, $newsletters->getCustomerId(), $newsletters->getEmail()),
                    Zend_Log::DEBUG
                );
            } else {
                $helper->log(sprintf(self::ERR_USER_SUBSCRIBED, $email), Zend_Log::WARN);
            }
        }
    }

    /**
     * Unsubscribes an email address from the Magento newsletter.
     *
     * @param string $email The email address to unsubscribe from Magento newsletter
     */
    public function unsubscribe($email)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Mage_Newsletter_Model_Subscriber $newsletters */
        $newsletters = Mage::getModel('newsletter/subscriber');
        $newsletters->loadByEmail($email);

        if ($newsletters->getId() === null) {
            $helper->log(sprintf(self::ERR_NO_EMAIL_FOUND, $email), Zend_Log::WARN);
            return;
        }

        if ($newsletters->isSubscribed() === false) {
            $helper->log(sprintf(self::ERR_USER_UNSUBSCRIBED, $email), Zend_Log::NOTICE);
            return;
        }

        $newsletters->setImportMode(true)->unsubscribe();
        $helper->log(
            sprintf(self::LOG_UNSUBSCRIBED_USER, $newsletters->getCustomerId(), $newsletters->getEmail()),
            Zend_Log::DEBUG
        );
    }
}
