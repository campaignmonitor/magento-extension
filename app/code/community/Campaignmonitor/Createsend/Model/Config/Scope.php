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
 * Provides _getScope function for getting scope and scope id given a website and/or store.
 *
 */
class Campaignmonitor_Createsend_Model_Config_Scope
{
    /**
     * Get the scope from $website or $store
     *
     * @param string $website
     * @param string $store
     * @return array
     */
    public function _getScope($website, $store)
    {
        // This code is duplicated from the _getScope method in adminhtml/core_config. It is duplicated because
        // that method is protected and we have need to determine the scope using the same logic.
        if ($store) {
            $scope   = 'stores';
            $scopeId = (int)Mage::getConfig()->getNode('stores/' . $store . '/system/store/id');
        } elseif ($website) {
            $scope   = 'websites';
            $scopeId = (int)Mage::getConfig()->getNode('websites/' . $website . '/system/website/id');
        } else {
            $scope   = 'default';
            $scopeId = Mage_Core_Model_App::ADMIN_STORE_ID;
        }

        return array($scope, $scopeId);
    }

    /**
     * Returns the Website Id given the scope/scopeId
     *
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    public function getWebsiteIdFromScope($scope, $scopeId)
    {
        $websiteId = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID)->getWebsiteId();

        if ($scope === 'websites') {
            $websiteId = $scopeId;
        } elseif ($scope === 'stores') {
           $websiteId = Mage::app()->getStore($scopeId)->getWebsiteId();
        }

        return $websiteId;
    }

    /**
     * Returns the Store Id given the scope/scopeId
     *
     * @param string $scope
     * @param int $scopeId
     * @return int
     * @throws Mage_Core_Exception
     */
    public function getStoreIdFromScope($scope, $scopeId)
    {
        $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;

        if ($scope === 'websites') {
            try {
                $storeId = Mage::app()->getWebsite($scopeId)->getDefaultGroup()->getDefaultStoreId();
            } catch (Exception $e) {
                // Use admin store id as above
            }
        } elseif ($scope === 'stores') {
            $storeId = $scopeId;
        }

        return $storeId;
    }
}
