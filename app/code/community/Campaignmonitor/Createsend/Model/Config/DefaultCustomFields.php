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

class Campaignmonitor_Createsend_Model_Config_DefaultCustomFields
{
    protected $_defaultCustomerAttributes = array(
        'FONTIS-has-account',
        'created_in',
        'group_id',
        'created_at',
        'firstname',
        'lastname',
        'dob',
        'gender',
        'confirmation',
        'website_id',
        'store_id',
        'FONTIS-sales-average-order-value',
        'FONTIS-sales-first-order-date',
        'FONTIS-sales-last-order-value',
        'FONTIS-sales-last-order-date',
        'FONTIS-sales-total-order-value',
        'FONTIS-sales-total-number-of-orders',
        'FONTIS-sales-total-number-of-products-ordered',
        'FONTIS-number-of-wishlist-items',
    );

    protected $_defaultProductAttributes = array(
        'name',
        'price',
        'short_description',
        'sku',
    );

    public function getDefaultCustomerAttributes()
    {
        return $this->_defaultCustomerAttributes;
    }

    public function getDefaultProductAttributes()
    {
        return $this->_defaultProductAttributes;
    }
}
