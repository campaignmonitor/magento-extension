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

class Campaignmonitor_Createsend_Adminhtml_Createsend_ApiController extends Mage_Adminhtml_Controller_Action
{
    const ADMINHTML_SYSTEM_CONFIG_EDIT  = 'adminhtml/system_config/edit';

    const ERR_API_CALL_ERROR            = 'API Test Error: %s';
    const LOG_API_CALL_SUCCESSFUL       = 'API Test Successful.';

    /**
     * Performs a test API call.
     *
     * @link https://www.campaignmonitor.com/api/
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function testAction()
    {
        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");

        $scope = $this->getRequest()->getQuery('scope');
        $scopeId = $this->getRequest()->getQuery('scopeId');

        /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
        $scopeModel = Mage::getModel('createsend/config_scope');
        $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        $reply = $api->call(
            Zend_Http_Client::GET,
            'lists/' . $helper->getListId($storeId),
            array(),
            array(),
            $scope,
            $scopeId
        );

        if ($reply['success'] === false) {
            $jsonData = json_encode(
                array(
                    'messages' => array(
                        array(
                            'status'    => 'error',
                            'message'   => sprintf(self::ERR_API_CALL_ERROR, $reply['data']['Message'])
                        )
                    )
                )
            );
        } else {
            $jsonData = json_encode(
                array(
                    'messages' => array(
                        array(
                            'status'    => 'success',
                            'message'   => self::LOG_API_CALL_SUCCESSFUL
                        )
                    )
                )
            );
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }


    /**
     * Returns a list of Campaign Monitor clients given the API Key.
     * Also saves the API Key for the scope.
     * If there is only one client, the lists for that client is also queried and returned.
     * Prints out the client list in JSON format.
     */
    public function getClientsAction()
    {
        $scope = $this->getRequest()->getQuery('scope');
        $scopeId = $this->getRequest()->getQuery('scopeId');
        $apiKey = $this->getRequest()->getQuery('apiKey');

        Mage::getConfig()->saveConfig(Campaignmonitor_Createsend_Helper_Data::XML_PATH_API_ID, $apiKey, $scope, $scopeId);

        /** @var $apiHelper Campaignmonitor_Createsend_Helper_Api */
        $apiHelper = Mage::helper("createsend/api");

        $clients = $apiHelper->getClients($scope, $scopeId);
        if ($clients !== null) {
            if (count($clients) == 2) {
                // Automatically get the lists if only one client
                $clients[1]['lists'] = $apiHelper->getLists($clients[1]['value'], $scope, $scopeId);
            }
        }

        $jsonData = json_encode(
            array (
                'status'    => 'success',
                'items'     => $clients
            )
        );

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

    /**
     * Returns the Campaign Monitor subscription lists for the client.
     * Prints out the lists in JSON format.
     */
    public function getListsAction()
    {
        $scope = $this->getRequest()->getQuery('scope');
        $scopeId = $this->getRequest()->getQuery('scopeId');
        $clientId = $this->getRequest()->getQuery('clientId');

        /** @var $apiHelper Campaignmonitor_Createsend_Helper_Api */
        $apiHelper = Mage::helper("createsend/api");

        $lists = $apiHelper->getLists($clientId, $scope, $scopeId);

        $jsonData = json_encode(
            array (
                'status'    => 'success',
                'items'     => $lists
            )
        );

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
}
