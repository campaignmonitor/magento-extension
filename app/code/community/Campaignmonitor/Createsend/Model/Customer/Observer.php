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
 * Responsible for taking action on Createsend as a result of some customer action.
 */
class Campaignmonitor_Createsend_Model_Customer_Observer
{
    const MSG_CUSTOM_FIELDS_CREATED             = 'Campaign Monitor custom fields created.';
    const MSG_CANNOT_DELETE_CORE_CUSTOM_FIELDS  = 'You cannot remove a core custom field. Core custom fields restored.';
    const MSG_DUPLICATE_CUSTOMER_FIELDS         = 'You have defined duplicate customer attributes: %s';
    const MSG_DUPLICATE_PRODUCT_FIELDS          = 'You have defined duplicate wishlist product attributes: %s';


    /**
     * Updates the subscription status of a user on campaign monitor based on user data in Magento
     *
     * @param Varien_Event_Observer $observer
     * @listen customer_save_before
     * @listen sales_order_save_after
     * @listen wishlist_product_add_after
     */
    public function checkSubscriptionStatus($observer)
    {
        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");
        $event = $observer->getEvent();
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $event->getCustomer();

        if (!$customer) {
            $order = $event->getOrder();
            if ($order) {
                $customerId = $order->getCustomerId();

                if (!$customerId) {
                    return;
                }

                $customer = Mage::getModel('customer/customer')->load($customerId);
            } else {
                $eventData = $event->getData();
                $items = $eventData['items'];

                if (count($items) > 0) {
                    /** @var Mage_Wishlist_Model_Item $item */
                    $item = $items[0];
                    $itemData = $item->getData();
                    /** @var Mage_Wishlist_Model_Wishlist $wishlist */
                    $wishlist = $itemData['wishlist'];
                    $wishlistData = $wishlist->getData();
                    $customerId = $wishlistData['customer_id'];

                    if (!$customerId) {
                        return;
                    }

                    $customer = Mage::getModel('customer/customer')->load($customerId);
                }
            }
        }

        $name = $customer->getName();
        $newEmail = $customer->getEmail();
        $subscribed = $customer->getIsSubscribed();
        $oldEmail = Mage::getModel('customer/customer')->load($customer->getId())->getEmail();

        // if subscribed is NULL (i.e. because the form didn't set it one way
        // or the other), get the existing value from the database
        if ($subscribed === null) {
            $subscribed = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer)->isSubscribed();
        }

        $customFields = Campaignmonitor_Createsend_Model_Customer_Observer::generateCustomFields($customer);

        /** @var Campaignmonitor_Createsend_Model_Api $api  */
        $api = Mage::getModel('createsend/api');

        $scope = 'stores';
        $scopeId = $customer->getStoreId();

        $storeId = $customer->getStoreId();

        if ($subscribed) {
            /* If the customer either:
               1) Already exists (i.e. has an old email address)
               2) Has changed their email address
               unsubscribe their old address. */
            if ($oldEmail && $newEmail !== $oldEmail) {
                $api->call(
                    Zend_Http_Client::POST,
                    "subscribers/{$helper->getListId($storeId)}/unsubscribe",
                    array(
                        'EmailAddress' => $oldEmail
                    ),
                    array(),
                    $scope,
                    $scopeId
                );
            }

            // Resubscribing during the adding process; otherwise someone who was unsubscribed will remain unsubscribed
            $api->call(
                Zend_Http_Client::POST,
                'subscribers/' . $helper->getListId($storeId),
                array(
                    'EmailAddress'                           => $newEmail,
                    'Name'                                   => $name,
                    'CustomFields'                           => $customFields,
                    'Resubscribe'                            => true,
                    'RestartSubscriptionBasedAutoresponders' => true
                ),
                array(),
                $scope,
                $scopeId
            );

        } else {
            $api->call(
                Zend_Http_Client::POST,
                "subscribers/{$helper->getListId($storeId)}/unsubscribe",
                array(
                    'EmailAddress' => $oldEmail
                ),
                array(),
                $scope,
                $scopeId
            );
        }
    }

    /**
     * Unsubscribes a customer when they are deleted.
     *
     * @param Varien_Event_Observer $observer
     * @listen customer_delete_before
     */
    public function customerDeleted($observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $email = $customer->getEmail();

        $scope = 'stores';
        $scopeId = $customer->getStoreId();

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        $api->unsubscribe($email, $scope, $scopeId);
    }

    /**
     * Creates custom fields in Campaign Monitor based on custom field mapping defined by Magento admin
     *
     * @param Varien_Event_Observer $observer
     * @listen admin_system_config_changed_section_createsend_customer
     * @throws Mage_Core_Exception if the API returns a bad result
     */
    public function createCustomFields(Varien_Event_Observer $observer)
    {
        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getSingleton('createsend/config_scope');
        list($scope, $scopeId) = $scopeModel->_getScope($observer->getWebsite(), $observer->getStore());

        /** @var Campaignmonitor_Createsend_Helper_Config $apiHelper */
        $apiHelper = Mage::helper('createsend/config');

        // Check that there haven't been duplicate custom fields
        $duplicateCustomerAttributes = $apiHelper->getDuplicateAttributes(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_M_TO_CM_ATTRIBUTES,
            $scope,
            $scopeId
        );
        if (count($duplicateCustomerAttributes)) {
            // Show warning if admin tries to add the same custom field multiple times
            Mage::getSingleton('adminhtml/session')->addWarning(
                sprintf(
                    self::MSG_DUPLICATE_CUSTOMER_FIELDS,
                    implode(
                        ', ',
                        $this->getAttributeLabels($duplicateCustomerAttributes, 'createsend/config_customerAttributes')
                    )
                )
            );
        }

        // Check that there haven't been duplicate product fields
        $duplicateProductAttributes = $apiHelper->getDuplicateAttributes(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_WISHLIST_PRODUCT_ATTRIBUTES,
            $scope,
            $scopeId
        );
        if (count($duplicateProductAttributes)) {
            // Show warning if admin tries to add the same custom product field multiple times
            Mage::getSingleton('adminhtml/session')->addWarning(
                sprintf(
                    self::MSG_DUPLICATE_PRODUCT_FIELDS,
                    implode(
                        ', ',
                        $this->getAttributeLabels($duplicateProductAttributes, 'createsend/config_productAttributes')
                    )
                )
            );
        }

        // Do not allow deleting of default/'core' custom fields
        $newFieldCount = $apiHelper->createDefaultCustomFields($scope, $scopeId);
        if ($newFieldCount > 0) {
            // Show warning message when a default/core custom field was attempted to be deleted
            Mage::getSingleton('adminhtml/session')->addWarning(self::MSG_CANNOT_DELETE_CORE_CUSTOM_FIELDS);
        }

        if ($observer->hasDataChanges()) {
            // Create customer custom fields
            $this->createAllCustomFields($scope, $scopeId);
        }
    }

    /**
     * Returns all the attribute labels for the list of attributes in $attributes
     * using the source model $classSpec
     *
     * @param array $attributes List of customer/product attributes
     * @param string $classSpec of class Campaignmonitor_Createsend_Model_Config_Attributes_Abstract
     * @return array
     */
    protected function getAttributeLabels($attributes, $classSpec)
    {
        /** @var Campaignmonitor_Createsend_Model_Config_Attributes_Abstract $source */
        $source = Mage::getModel($classSpec);

        $labels = array();
        foreach ($attributes as $attribute) {
            $labels[] = $source->getFieldLabel($attribute);
        }

        return $labels;
    }

    /**
     * Creates custom fields in Campaign Monitor for the scope/scopeId
     *
     * @param string $scope
     * @param int $scopeId
     * @throws Mage_Core_Exception
     */
    public function createAllCustomFields($scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        $customerErrors = $api->createCustomerCustomFields($scope, $scopeId);
        $wishlistErrors = $api->createWishlistCustomFields($scope, $scopeId);

        $errors = array_merge_recursive($customerErrors, $wishlistErrors);

        if (count($errors)) {
            foreach ($errors as $error => $fields) {
                $errorMsg = sprintf(
                    $api::ERR_CANNOT_CREATE_CUSTOM_FIELD,
                    implode(', ', $fields),
                    $error
                );

                Mage::getSingleton('adminhtml/session')->addError($errorMsg);
            }
        }
    }

    /**
     * Generate an array of custom fields based on a config setting and customer data.
     * Customer data includes purchase and wish list products data.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    public static function generateCustomFields($customer)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        /** @var Campaignmonitor_Createsend_Model_Config_CustomerAttributes $attrSource */
        $attrSource = Mage::getSingleton('createsend/config_customerAttributes');

        if ($customer->getId()) {
            $storeId = $customer->getStoreId();
        } else {
            $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
        }
        $linkedAttributes = @unserialize(Mage::getStoreConfig($helper::XML_PATH_M_TO_CM_ATTRIBUTES, $storeId));

        $customFields = array();
        if (!empty($linkedAttributes)) {
            if ($customer->getId()) {
                $customerData = $customer->getData();
                Mage::log("Customer exists " . print_r($customerData, true));
            } else {
                $customerData = array();
                Mage::log("Customer doesn't exist ");
            }
            foreach ($linkedAttributes as $la) {
                $magentoAtt = $la['magento'];
                $cmAtt = $api->formatCustomFieldName($attrSource->getCustomFieldName($la['magento'], true));
                Mage::log($cmAtt);


                // try and translate IDs to names where possible
                if ($magentoAtt == 'group_id') {
                    if ($customer->getId()) {
                        $d = Mage::getModel('customer/group')->load($customer->getGroupId())->getData();
                        if (array_key_exists('customer_group_code', $d)) {
                            $customFields[] = array("Key" => $cmAtt, "Value" => $d['customer_group_code']);
                        }
                    }
                } elseif ($magentoAtt == 'website_id') {
                    if ($customer->getId()) {
                        $d = Mage::app()->getWebsite($customer->getWebsiteId())->getData();
                        if (array_key_exists('name', $d)) {
                            $customFields[] = array("Key" => $cmAtt, "Value" => $d['name']);
                        }
                    }
                } elseif ($magentoAtt == 'store_id') {
                    if ($customer->getId()) {
                        $d = Mage::app()->getStore($customer->getStoreId())->getData();
                        if (array_key_exists('name', $d)) {
                            $customFields[] = array("Key" => $cmAtt, "Value" => $d['name']);
                        }
                    }
                } elseif ($magentoAtt == 'gender') {

                    $gender = "";
                    if (array_key_exists($gender, $customerData)){
                        $gender = $customer->getAttribute($magentoAtt)->getSource()->getOptionText($customerData[$magentoAtt]);
                    }

                    $customFields[] = array("Key" => $cmAtt, "Value" => $gender);
                } elseif ($magentoAtt == 'confirmation') {
                    // This attribute should have been named confirmation_key
                    // If not yet confirmed, this attribute will contain the confirmation key
                    // Once confirmed, this attribute will be empty
                    $confirmed = empty($customerData[$magentoAtt]) ? 'Yes' : 'No';
                    $customFields[] = array("Key" => $cmAtt, "Value" => $confirmed);
                } elseif ($magentoAtt == 'FONTIS-has-account') {
                    if ($customer->getId()) {
                        $customFields[] = array('Key' => $cmAtt, 'Value' => 'Yes');
                    } else {
                        $customFields[] = array('Key' => $cmAtt, 'Value' => 'No');
                    }
                } elseif ($magentoAtt == 'FONTIS-number-of-wishlist-items') {
                    if ($customer->getId()) {
                        /** @var Mage_Wishlist_Model_Wishlist $wishList */
                        $wishList = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer);

                        $customFields[] = array('Key' => $cmAtt, 'Value' => $wishList->getItemsCount());
                    }
                } elseif (strncmp('FONTIS-sales', $magentoAtt, 12) == 0) {
                    $purchaseData = array();

                    if ($customer->getId()) {
                        if (strncmp('FONTIS-sales-last-order', $magentoAtt, 23) == 0) {
                            // get last order
                            $lastOrder = self::_getEndOrder(
                                $customer->getId(), 'DESC', array(
                                'grand_total',
                                'created_at'
                            )
                            );
                            $purchaseData['last-order-value'] = $lastOrder->getGrandTotal();
                            $purchaseData['last-order-date'] = $lastOrder->getCreatedAt();
                        } elseif (strncmp('FONTIS-sales-first-order', $magentoAtt, 24) == 0) {
                            // get first order
                            $firstOrder = self::_getEndOrder($customer->getId(), 'ASC', array('created_at'));
                            $purchaseData['first-order-date'] = $firstOrder->getCreatedAt();
                        } else {
                            $orderCollection = Mage::getModel('sales/order')->getCollection()
                                ->addFieldToFilter('customer_id', $customer->getId())
                                ->addFieldToFilter('status', array('neq' => 'canceled'))
                                ->addAttributeToSelect('grand_total')
                                ->addAttributeToSelect('total_qty_ordered');

                            $orderTotals = $orderCollection->getColumnValues('grand_total');

                            // get total order value
                            $purchaseData['total-order-value'] = array_sum($orderTotals);

                            // get total number of orders
                            $purchaseData['total-number-of-orders'] = count($orderTotals);

                            // get average order value
                            if ($purchaseData['total-number-of-orders'] > 0) {
                                $purchaseData['average-order-value'] =
                                    round($purchaseData['total-order-value'] / $purchaseData['total-number-of-orders'], 4);
                            } else {
                                $purchaseData['average-order-value'] = 0;
                            }

                            // get total number of products ordered
                            $productTotals = $orderCollection->getColumnValues('total_qty_ordered');
                            $purchaseData['total-number-of-products-ordered'] = array_sum($productTotals);
                        }

                        $purchaseAtt = substr($magentoAtt, 13, strlen($magentoAtt));
                        $customFields[] = array("Key" => $cmAtt, "Value" => $purchaseData[$purchaseAtt]);
                    }

                } elseif (strncmp('FONTIS', $magentoAtt, 6) == 0) {
                    if ($customer->getId()) {
                        if (strncmp('FONTIS-billing', $magentoAtt, 14) == 0) {
                            $d = $customer->getDefaultBillingAddress();
                            if ($d) {
                                $d = $d->getData();
                                $addressAtt = substr($magentoAtt, 15, strlen($magentoAtt));
                            }
                        } else {
                            $d = $customer->getDefaultShippingAddress();
                            if ($d) {
                                $d = $d->getData();
                                $addressAtt = substr($magentoAtt, 16, strlen($magentoAtt));
                            }
                        }

                        if ($d && $addressAtt == 'region_id') {
                            if (array_key_exists('region_id', $d)) {
                                $region = Mage::getModel('directory/region')->load($d['region_id']);
                                $customFields[] = array("Key" => $cmAtt, "Value" => $region->getName());
                            }
                        } elseif ($d) {
                            if (array_key_exists($addressAtt, $d)) {
                                $customFields[] = array("Key" => $cmAtt, "Value" => $d[$addressAtt]);
                            }
                        }
                    }
                } else {
                    if (array_key_exists($magentoAtt, $customerData)) {

                        $attribute = $customer->getAttribute($magentoAtt);
                        if ($attribute->getFrontendInput() == 'select' || $attribute->getSourceModel()) {
                            $label = $attribute->getSource()->getOptionText($customerData[$magentoAtt]);
                            $customFields[] = array("Key" => $cmAtt, "Value" => $label);
                        } else {
                            $customFields[] = array("Key" => $cmAtt, "Value" => $customerData[$magentoAtt]);
                        }
                    }
                }
            }
        }

        if ($customer->getId()) {
            /** @var Campaignmonitor_Createsend_Model_Config_ProductAttributes $attrSource */
            $attrSource = Mage::getSingleton('createsend/config_productAttributes');

            $productAttributes = @unserialize(
                Mage::getStoreConfig($helper::XML_PATH_WISHLIST_PRODUCT_ATTRIBUTES, $storeId)
            );

            /** @var Mage_Wishlist_Model_Wishlist $wishList */
            $wishList = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer);
            $wishListItemCollection = $wishList->getItemCollection();

            $maxWishlistItems = $helper->getMaxWistlistItems($storeId);

            if (!empty($productAttributes) & !empty($maxWishlistItems)) {
                $count = 0;
                foreach ($wishListItemCollection as $item) {
                    if ($count >= $maxWishlistItems) {
                        break;
                    }

                    $count++;
                    $product = $item->getProduct();

                    foreach ($productAttributes as $pa) {
                        $magentoAtt = $pa['magento'];
                        $cmAtt = $attrSource->getCustomFieldName($pa['magento'], true);

                        if (strncmp($magentoAtt, "price", 5) == 0) {
                            $value = $product->getFinalPrice();
                        } else {
                            $value = $product->getData($magentoAtt);
                        }

                        $customFields[] = array(
                            "Key"   => $api->formatCustomFieldName(
                                sprintf(
                                    $api::WISHLIST_CUSTOM_FIELD_PATTERN, $api::WISHLIST_CUSTOM_FIELD_PREFIX, $count, $cmAtt
                                )
                            ),
                            "Value" => $value
                        );
                    }
                }

                // Clear out other items, if any
                for ($i = $count + 1; $i <= $maxWishlistItems; $i++) {
                    foreach ($productAttributes as $pa) {
                        $cmAtt = $attrSource->getCustomFieldName($pa['magento'], true);

                        $customFields[] = array(
                            "Key"   => $api->formatCustomFieldName(
                                sprintf($api::WISHLIST_CUSTOM_FIELD_PATTERN, $api::WISHLIST_CUSTOM_FIELD_PREFIX, $i, $cmAtt)
                            ),
                            "Clear" => true
                        );
                    }
                }
            }
        }

        return $customFields;
    }

    /**
     * Returns the first or last order of customer given the customer's customerId.
     * Excludes cancelled orders.
     *
     * @param int $customerId Customer ID
     * @param string $sortOrder Use "ASC" to return first order, "DESC" to return last order
     * @param array $selectFields fields to be selected
     * @return Mage_Sales_Model_Order
     */
    protected static function _getEndOrder($customerId, $sortOrder, $selectFields) {
        /** @var Mage_Sales_Model_Order[]|Mage_Sales_Model_Resource_Order_Collection $orders */
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', array('neq' => 'canceled'))
            ->addAttributeToSort('created_at', $sortOrder)
            ->setPageSize(1);

        foreach ($selectFields as $field) {
            $orders->addFieldToSelect($field);
        }

        return $orders->getFirstItem();
    }
}
