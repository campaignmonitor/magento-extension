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

abstract class Campaignmonitor_Createsend_Block_AjaxButton_Abstract extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /** @var string */
    protected $_urlPath;

    /** @var string */
    protected $_ajaxUrl;

    /** @var string */
    protected $_buttonHtml;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('campaignmonitor/createsend/ajax.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var Mage_Core_Model_Config_Data $configData */
        $configData = Mage::getSingleton('adminhtml/config_data');

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getSingleton('createsend/config_scope');
        list($scope, $scopeId) = $scopeModel->_getScope($configData->getWebsite(), $configData->getStore());

        $this->_ajaxUrl = $this->getUrl(
            $this->_urlPath,
            array(
                '_query'    => array(
                    'scope'     => $scope,
                    'scopeId'   => $scopeId,
                )
            )
        );

        $this->setElement($element);

        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($element->getLabel())
            ->setOnClick('javascript:' . $this->getAjaxFunction(). '(); return false;')
            ->setId($element->getId());

        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper("createsend");

        if (!$helper->isCompleteConfig($scope, $scopeId)) {
            $button->setDisabled('disabled');
        }

        $this->_buttonHtml = $button->toHtml();

        return $this->_toHtml();
    }

    /**
     * Returns the Javascript function name for performing the Ajax call.
     *
     * @return string
     */
    public function getAjaxFunction()
    {
        return $this->getElement()->getId() . 'Ajax';
    }

    /**
     * Returns the AJAX URL to test the API
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->_ajaxUrl;
    }

    /**
     * Returns the HTML for the custom API Test button
     *
     * @return string
     */
    public function getButtonHtml()
    {
        return $this->_buttonHtml;
    }
}