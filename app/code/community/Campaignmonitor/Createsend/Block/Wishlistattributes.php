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

class Campaignmonitor_Createsend_Block_Wishlistattributes extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $magentoOptions;

    public function __construct()
    {
        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");

        $this->addColumn('magento', array(
            'label' => $helper->__('Product attribute'),
            'size'  => 28,
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = $helper->__('Add product attribute');

        parent::__construct();
        $this->setTemplate('campaignmonitor/createsend/system/config/form/field/array_dropdown.phtml');

        $options = Mage::getSingleton('createsend/config_productAttributes')->toOptionArray();
        $this->magentoOptions = array();
        foreach ($options as $option) {
            $this->magentoOptions[$option['value']] = $option['label'];
        }

        asort($this->magentoOptions);
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($columnName == 'magento') {
            $rendered = '<select name="'.$inputName.'">';
            foreach ($this->magentoOptions as $att => $name) {
                $rendered .= '<option value="'.$att.'">'.$name.'</option>';
            }
            $rendered .= '</select>';
        } else {
            return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . '/>';
        }

        return $rendered;
    }
}
