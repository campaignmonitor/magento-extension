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

abstract class Campaignmonitor_Createsend_Model_Cron
{
    const LOG_ITERATOR_START    = 'Scope Iterator Start.';
    const LOG_ITERATOR_END      = 'Scope Iterator End.';
    const LOG_ITERATOR_ITEM     = 'Calling %1$s for %2$s...';

    /**
     * The method to register in config.xml for Magento cron.
     * Only executes the runJob method if cron is enabled in configuration.
     */
    public function runFromMagentoCron()
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $useMagentoCron = $helper->isMagentoCronEnabled(Mage_Core_Model_App::ADMIN_STORE_ID);

        if ($useMagentoCron) {
            $this->runJob();
        }
    }

    /**
     * Subclasses must implement this method which will be executed if cron is enabled
     *
     * @return mixed
     */
    abstract function runJob();

    /**
     * Executes function(s) in $functions array for all scopes where $configPath configuration value is defined
     * (not inherited) and $helper->$helperConfig($storeId) is true.
     *
     * Passes the values for the configuration value for $configPath ($configValue), $scope and $scopeId
     * to the function(s), in this order.
     *
     * $functions array should be in the form of:
     *
     * <pre>
     *  array(
     *      array(
     *          'class'     => $object,
     *          'method'    => 'method1',
     *      ),
     *      array(
     *          'class'     => $object,
     *          'method'    => 'method2',
     *      ),
     *  )
     * </pre>
     *
     * @param string $configPath The path to the configuration value, eg. 'createsend_general/api/list_id'
     * @param string $helperConfigChecker The name of the helper method which determines if the function(s) should
     *                                    should be executed for the particular scope/scopeId
     * @param array $functions An array of object and method pair(s) to execute by calling call_user_func_array()
     */
    public function iterateScopes($configPath, $helperConfigChecker, array $functions = array())
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $helper->log(self::LOG_ITERATOR_START);

        // Get all scopes where List ID is defined (not inherited)
        $configModel = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', $configPath)
            ->addFieldToSelect('value')
            ->addFieldToSelect('scope')
            ->addFieldToSelect('scope_id');

        foreach ($configModel as $config) {
            $configValue = $config->getValue();
            $scope = $config->getScope();
            $scopeId = $config->getScopeId();

            /** @var Campaignmonitor_Createsend_Model_Config_Scope $scopeModel */
            $scopeModel = Mage::getModel('createsend/config_scope');
            $storeId = $scopeModel->getStoreIdFromScope($scope, $scopeId);

            $configEnabled = call_user_func_array(array($helper, $helperConfigChecker), array($storeId));
            if ($configEnabled) {
                foreach ($functions as $function) {
                    $object = $function['class'];
                    $method = $function['method'];

                    $helper->log(sprintf(self::LOG_ITERATOR_ITEM, $method, $configValue));

                    call_user_func_array(
                        array($object, $method),
                        array($configValue, $scope, $scopeId)
                    );
                }
            }
        }

        $helper->log(self::LOG_ITERATOR_END);
    }
}