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

class Campaignmonitor_Createsend_Model_Config_Api
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function addJavascriptBlock($observer)
    {
        $controller = $observer->getAction();
        $layout = $controller->getLayout();

        // Load only if in our section
        $params = $controller->getRequest()->getParams();
        if (array_key_exists('section', $params) && $params['section'] === 'createsend_general') {
            $block = $layout->createBlock('adminhtml/template');
            $block->setTemplate('campaignmonitor/createsend/apiclient.phtml');
            $layout->getBlock('js')->append($block);
        }
    }
}