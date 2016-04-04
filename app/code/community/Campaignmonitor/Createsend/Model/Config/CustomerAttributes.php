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
class Campaignmonitor_Createsend_Model_Config_CustomerAttributes
    extends Campaignmonitor_Createsend_Model_Config_Attributes_Abstract
{
    const ADDRESS_FIELD_PREFIX      = 'FONTIS-';

    /** @var array $_customFieldNameMapping */
    protected $_customFieldNameMapping = array(
        'confirmation'              => 'Customer Email Is Confirmed',
        'created_at'                => 'Customer Date Created',
        'created_in'                => 'Customer Created From Store',
        'customer_activated'        => 'Customer Account Activated',
        'dob'                       => 'Customer Date Of Birth',
        'firstname'                 => 'Customer First Name',
        'gender'                    => 'Customer Gender',
        'group_id'                  => 'Customer Group',
        'lastname'                  => 'Customer Last Name',
        'middlename'                => 'Customer Middle Name',
        'prefix'                    => 'Customer Prefix',
        'store_id'                  => 'Store',
        'suffix'                    => 'Customer Suffix',
        'taxvat'                    => 'Customer Tax/VAT Number',
        'website_id'                => 'Customer Website',
        'FONTIS-billing-firstname'  => 'Billing Customer First Name',
        'FONTIS-billing-lastname'   => 'Billing Customer Last Name',
        'FONTIS-shipping-firstname' => 'Shipping Customer First Name',
        'FONTIS-shipping-lastname'  => 'Shipping Customer Last Name',
    );

    // Put all extra custom attributes here (Do not put billing and sales attributes here)
    /** @var array $_extraAttributes */
    protected $_extraAttributes = array(
        'FONTIS-has-account' => array(
            'label'     => 'Has Customer Account',
            'type'      => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_SELECT_ONE,
            'options'   => array('Yes', 'No')
        ),
        'FONTIS-number-of-wishlist-items' => array(
            'label' => 'Number of Wishlist Items',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_NUMBER
        )
    );

    /** @var array $_excludedAttributes */
    protected $_excludedAttributes = array(
        'entity_type_id',
        'entity_id',
        'attribute_set_id',
        'password_hash',
        'increment_id',
        'updated_at',
        'email',
        'default_billing',
        'default_shipping',
        'disable_auto_group_change',
        'rp_token',
        'rp_token_created_at',
    );

    // Attribute name to be displayed in Magento
    /** @var array $_attributeNames */
    protected $_attributeNames = array(
        'store_id'      => 'Store',
        'group_id'      => 'Customer Group',
        'website_id'    => 'Website',
        'created_at'    => 'Date Created',
    );

    /** @var array $_addressFields */
    protected $_addressFields = array(
        'firstname'     => 'First Name',
        'lastname'      => 'Last Name',
        'company'       => 'Company',
        'telephone'     => 'Phone',
        'fax'           => 'Fax',
        'street'        => 'Street',
        'city'          => 'City',
        'region_id'     => 'State/Province',
        'postcode'      => 'Zip/Postal Code',
        'country_id'    => 'Country',
    );

    /** @var array $_addressTypes */
    protected $_addressTypes = array(
        'billing'       => 'Billing',
        'shipping'      => 'Shipping',
    );

    /** @var array $_salesAttributes */
    protected $_salesAttributes = array(
        'FONTIS-sales-last-order-value' => array(
            'label' => 'Last Order Value',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_NUMBER
        ),
        'FONTIS-sales-last-order-date' => array(
            'label' => 'Last Order Date',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_DATE
        ),
        'FONTIS-sales-average-order-value' => array(
            'label' => 'Average Order Value',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_NUMBER
        ),
        'FONTIS-sales-total-order-value' => array(
            'label' => 'Total Order Value',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_NUMBER
        ),
        'FONTIS-sales-total-number-of-orders' => array(
            'label' => 'Total Number Of Orders',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_NUMBER
        ),
        'FONTIS-sales-total-number-of-products-ordered' => array(
            'label' => 'Total Quantity Ordered',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_NUMBER
        ),
        'FONTIS-sales-first-order-date' => array(
            'label' => 'First Order Date',
            'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_DATE
        ),
    );

    public function __construct()
    {
        $this->_fields = array();

        // Add extra attributes
        $this->_fields = array_merge($this->_fields, $this->_extraAttributes);

        /** @var Mage_Customer_Model_Customer $customerModel */
        $customerModel = Mage::getModel('customer/customer');
        $magentoAttributes = $customerModel->getAttributes();

        foreach (array_keys($magentoAttributes) as $att) {
            $attribute = $customerModel->getAttribute($att);

            if (!in_array($att, $this->_excludedAttributes)) {
                $label = $attribute->getFrontendLabel();

                // give nicer names to the attributes
                if (isset($this->_attributeNames[$att])) {
                    $name = $this->_attributeNames[$att];
                } elseif (!empty($label)) {
                    $name = $attribute->getFrontendLabel();
                } else {
                    $name = $att;
                }

                // Get the attribute type for Campaign Monitor
                if ($attribute->getFrontendInput() === 'date' || $attribute->getFrontendInput() === 'datetime') {
                    $type = Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_DATE;
                } elseif ($attribute->getBackendType() == 'int' && $attribute->getFrontendInput() == 'text') {
                    $type = Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_NUMBER;
                } elseif ($attribute->getBackendType() == 'decimal') {
                    $type = Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_NUMBER;
                } elseif ($att == 'gender' || $att == 'confirmation') {
                    $type = Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_SELECT_ONE;
                } elseif ($this->isBooleanAttribute($attribute)) {
                    $type = Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_SELECT_ONE;
                } else {
                    $type = Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_TEXT;
                }

                // Populate the field list
                $this->_fields[$att] = array(
                    'label' => $name,
                    'type'  => $type
                );
                if ($type === Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_SELECT_ONE) {
                    if ($att == 'confirmation') {
                        $this->_fields[$att]['options'] = array('Yes', 'No');
                    } else {
                        $allOptions = $attribute->getSource()->getAllOptions(false);

                        $options = array();
                        foreach ($allOptions as $option) {
                            $options[] = $option['label'];
                        }

                        $this->_fields[$att]['options'] = $options;
                    }
                }
            }
        }
        asort($this->_fields);

        $this->_fields = array_merge($this->_fields, $this->getAddressFields('Billing'));
        $this->_fields = array_merge($this->_fields, $this->getAddressFields('Shipping'));

        $this->_fields = array_merge($this->_fields, $this->_salesAttributes);
    }

    /**
     * Returns true if the attribute type is boolean based on source model.
     * Returns false otherwise.
     *
     * @param Mage_Customer_Model_Entity_Attribute $attribute
     * @return bool
     */
    public function isBooleanAttribute($attribute)
    {
        return $attribute->getSourceModel()
            && $attribute->getSourceModel() == 'eav/entity_attribute_source_boolean';
    }

    /**
     * Returns all attribute option labels in an array
     *
     * @param string $field
     * @return array
     */
    public function getFieldOptions($field)
    {
        if (array_key_exists($field, $this->_fields)
            && $this->_fields[$field]['type'] == Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_SELECT_ONE) {

            return $this->_fields[$field]['options'];
        } else {
            return array();
        }
    }

    /**
     * Returns an array of address attributes based on address type
     *
     * @param string $addressType "Billing" or "Shipping"
     * @return array
     */
    protected function getAddressFields($addressType)
    {
        $fields = array();

        foreach ($this->_addressFields as $att => $label) {
            $fields[self::ADDRESS_FIELD_PREFIX . strtolower($addressType) . '-' . $att] = array(
                'label' => sprintf('%s Address: %s', $addressType, $label),
                'type'  => Campaignmonitor_Createsend_Model_Api::FIELD_TYPE_TEXT
            );
        }

        return $fields;
    }

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

        if (array_key_exists($field, $this->_attributeNames)) {
            return $this->_attributeNames[$field];
        }

        if (array_key_exists($field, $this->_extraAttributes)) {
            return $this->_extraAttributes[$field]['label'];
        }

        if (array_key_exists($field, $this->_salesAttributes)) {
            return $this->_salesAttributes[$field]['label'];
        }

        foreach ($this->_addressTypes as $addressType => $addressTypeLabel) {
            $typePrefix = self::ADDRESS_FIELD_PREFIX . $addressType . '-';

            // Check if the field is one of these address types
            if (0 === strpos($field, $typePrefix)) {
                // Remove the prefix from the field name
                $addressField = substr($field, strlen($typePrefix));

                if (array_key_exists($addressField, $this->_addressFields)) {
                    $addressFieldLabel = $this->_addressFields[$addressField];

                    return sprintf('%s %s', $addressTypeLabel, $addressFieldLabel);
                } else {
                    return sprintf('%s %s', $addressTypeLabel, ucwords($addressField));
                }
            }
        }

        if ($returnDefault) {
            return ucwords($field);
        } else {
            return null;
        }
    }
}
