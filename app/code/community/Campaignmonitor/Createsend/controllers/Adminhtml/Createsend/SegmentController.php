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
class Campaignmonitor_Createsend_Adminhtml_Createsend_SegmentController extends Mage_Adminhtml_Controller_Action
{
    const ADMINHTML_SYSTEM_CONFIG_EDIT  = 'adminhtml/system_config/edit';

    /**
     * Responsible for creating Campaign Monitor segments.
     *
     * @link https://www.campaignmonitor.com/api/segments/
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function createExamplesAction()
    {
        $scope = $this->getRequest()->getQuery('scope');
        $scopeId = $this->getRequest()->getQuery('scopeId');

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        $responses = $api->createExampleSegments($scope, $scopeId);

        print json_encode(
            array(
                'messages'   => $responses
            )
        );
    }
}
