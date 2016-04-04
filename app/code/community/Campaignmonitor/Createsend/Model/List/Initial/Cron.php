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

class Campaignmonitor_Createsend_Model_List_Initial_Cron extends Campaignmonitor_Createsend_Model_Cron
{
    const PROCESS_ID            = 'list_initial_cron';

    /**
     * Performs list subscribers synchronisation for all scopes w/ non-inherited List ID
     */
    public function runJob()
    {
        $this->_fullSync();
    }

    /**
     * Synchronises list subscribers from Magento to Campaign Monitor
     *
     */
    public function _fullSync()
    {
        $flagClass = 'createsend/config_listFlag';

        /** @var Campaignmonitor_Createsend_Helper_Config $configHelper */
        $configHelper = Mage::helper('createsend/config');

        /** @var array $processData */
        $processData = $configHelper->getFlagData($flagClass, self::PROCESS_ID);

        if (!is_array($processData)) {
            $processData = array(
                'status'    => $configHelper::FLAG_PROCESS_STOPPED,
                'start'     => null,
                'end'       => null,
            );
        }

        // Check for stale process
        if ($processData['status'] === $configHelper::FLAG_PROCESS_RUNNING) {
            $startDate = new DateTime($processData['start']);
            $now = new DateTime('now');
            $dateDiff = $startDate->diff($now);

            if ($dateDiff->days < 1) {
                // running less than one day, not yet stale
                return;
            }

            // If this stage is reached, process is considered stale.
            // Continue processing
        }

        // Set and save the flag so that this process does not run more than once
        $processData['status'] = $configHelper::FLAG_PROCESS_RUNNING;
        $processData['start'] = date('Y-m-d H:i:s');
        $configHelper->addFlagData($flagClass, self::PROCESS_ID, $processData);

        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('createsend/api');

        $updatedLists = array();

        $stores = Mage::app()->getStores(true);
        foreach ($stores as $storeId => $store) {
            $listId = $helper->getListId($storeId);

            if (!$listId) {
                // Don't have a list to use
                continue;
            }

            $flagData = $configHelper->getFlagData($flagClass, $listId);

            if (!$flagData) {
                // No list data has been saved yet
                continue;
            }

            if (isset($flagData[$configHelper::INDEX_INITIAL_SYNC]) &&
                $flagData[$configHelper::INDEX_INITIAL_SYNC] === $configHelper::FLAG_STATUS_NEW) {
                // We have initial sync info and it's in the appropriate state so run the full sync
                $apiHelper->performFullSync($storeId);

                $updatedLists[$listId] = true;
            }
        }

        foreach (array_keys($updatedLists) as $listId) {
            // Set flag status to 'done' for all synchronized lists
            $listFlagData[$configHelper::INDEX_INITIAL_SYNC] = $configHelper::FLAG_STATUS_DONE;
            $configHelper->addFlagData($flagClass, $listId, $listFlagData);
        }

        // Done processing, update flag for this process
        $processData['status'] = $configHelper::FLAG_PROCESS_STOPPED;
        $processData['end'] = date('Y-m-d H:i:s');
        $configHelper->addFlagData($flagClass, self::PROCESS_ID, $processData);
    }
}
