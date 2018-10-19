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
 * Campaign Monitor OAuth Redirect URL information.
 *
 */
class Campaignmonitor_Createsend_Block_Log_View extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {

        $style =  '<style>';
        $style .= '#row_createsend_general_debug_log_content  td:nth-child(2) { width: 80% }';
        $style .= '#createsend_general_debug_log_content  { width: 95%; min-height: 20em; background:#000; color:#20ff20; font-family:Consolas; }';
        $style .= '#createsend_general_debug_log_content::selection  {  background:#0b26da; color:#fff; }';
        $style .= '#createsend_general_debug_log_content::-moz-selection  { background:#0b26da; color:#fff; }';
        $style .= '</style>';

        $script =  '<script>';
        $script .= 'var logViewer = document.getElementById("createsend_general_debug_log_content");';
        $script .= 'logViewer.onclick = function() { logViewer.focus();';
        $script .= 'logViewer.select(); };';
        $script .= '</script>';

        $html = $element->getAfterElementHtml();
        $element->setAfterElementHtml($html . $style . $script);

        return parent::_getElementHtml($element);
    }
}