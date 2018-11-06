<?php

/*
 * Make sure the upgrade is not performed on installations without the tables
 * (i.e. unpatched shops).
 */
$adminVersion = Mage::getConfig()->getModuleConfig('Mage_Admin')->version;
if (version_compare($adminVersion, '1.6.1.2', '>=')) {

    $blockNames = array(
        'campaignmonitor/createsend',
        'createsend/email',
        'createsend/api',
        'createsend/adminhtml_customer_edit_grid',
        'createsend/adminhtml_switcher',
        'createsend/adminhtml_email',
    );
    foreach ($blockNames as $blockName) {
        $whitelistBlock = Mage::getModel('admin/block')->load($blockName, 'block_name');
        $whitelistBlock->setData('block_name', $blockName);
        $whitelistBlock->setData('is_allowed', 1);
        $whitelistBlock->save();
    }
    
}

Mage::log('updated successfully to version 1.0.7', null, 'campaign-monitor-upgrade.log', true);