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

class Campaignmonitor_Createsend_Block_Adminhtml_Email_View_Tab_Text
    extends Campaignmonitor_Createsend_Block_Adminhtml_Email_View_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return parent::getTabLabel();
    }

    public function getTabTitle()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return parent::getTabTitle();
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        /** @var array $email */
        $email = $this->getEmail();
        if (empty($email['Message']['Body']['Text'])) {
            return true;
        } else {
            return false;
        }
    }
}
