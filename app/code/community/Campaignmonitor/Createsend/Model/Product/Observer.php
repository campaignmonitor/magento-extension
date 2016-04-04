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
 * Responsible for updating subscribers' custom field values when a product is changed
 */
class Campaignmonitor_Createsend_Model_Product_Observer
{
    /**
     * Updates the (wishlist) custom fields of a user on campaign monitor based on new product data in Magento
     *
     * @param Varien_Event_Observer $observer
     * @listen catalog_product_save_after
     */
    public function updateWishlistFields($observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();

        /** @var Campaignmonitor_Createsend_Model_Api $api  */
        $api = Mage::getModel('createsend/api');

        // Get all subscribers having the product in their wishlists
        $wishlistCollection = Mage::getModel('wishlist/item')
            ->getCollection()
            ->addFieldToFilter('product_id', $product->getId())
            ->addFieldToSelect('wishlist_id')
            ->join(
                array('wishlist' => 'wishlist/wishlist'),
                'main_table.wishlist_id = wishlist.wishlist_id',
                array('customer_id')
            );

        foreach ($wishlistCollection as $wishlist) {
            $customerId = $wishlist->getCustomerId();

            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getSingleton('customer/customer')->load($customerId);
            if ($customer) {
                /** @var Mage_Newsletter_Model_Subscriber $newsletter */
                $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
                $isSubscribed = $subscriber->isSubscribed();

                if ($isSubscribed) {
                    $scope = 'stores';
                    $scopeId = $subscriber->getStoreId();

                    $api->subscribe($subscriber->getSubscriberEmail(), $scope, $scopeId);
                }
            }
        }
    }
}
