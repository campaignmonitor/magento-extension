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
 * Product attributes to be included in the custom fields to be sent to Campaign Monitor
 *
 */
class Campaignmonitor_Createsend_Model_Config_ProductAttributes
    extends Campaignmonitor_Createsend_Model_Config_Attributes_Abstract
{
    /** @var array $_customFieldNameMapping */
    protected $_customFieldNameMapping = array(
        // Define custom field names for product attributes here
//        'short_description'
    );

    protected $_fields = array(
        'name'  =>  array(
            'label' => 'Name',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_TEXT
        ),
        'price' => array(
            'label' => 'Price',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_NUMBER
        ),
        'short_description' => array(
            'label' => 'Description',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_TEXT
        ),
        'sku' => array(
            'label' => 'SKU',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_TEXT
        ),
        'url_key' => array(
            'label' => 'URL Key',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_TEXT
        ),
        'url_path' => array(
            'label' => 'URL Path',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_TEXT
        ),
    );

    /**
     * Returns the Campaign Monitor custom field name given the Magento attribute name.
     *
     * @param string $field The Magento attribute name
     * @param bool $returnDefault If true, returns the default value. Otherwise, returns null.
     * @return null|string
     */
    public function getCustomFieldName($field, $returnDefault = true)
    {
        $custom = parent::getCustomFieldName($field, false);
        if ($custom !== null) {
            return $custom;
        }

        if (array_key_exists($field, $this->_fields)) {
            return $this->_fields[$field]['label'];
        }

        if ($returnDefault) {
            return ucwords($field);
        } else {
            return null;
        }
    }
}