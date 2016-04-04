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
try {
    // turn on extra customer fields
    Mage::getConfig()->saveConfig('customer/address/dob_show', 'opt', 'default', 0);
    Mage::getConfig()->saveConfig('customer/address/gender_show', 'opt', 'default', 0);
} catch (Exception $e){
    Mage::log($e->getMessage());
}
/** @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();
$connection = $this->getConnection();

$emailTableName = $this->getTable("createsend/email");

$emailTable = $connection->newTable($emailTableName)
    ->addColumn('email_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
            'identity'  => true,
        ),
        'Email ID'
    )
    ->addColumn(
        'message_id',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        64,
        array(
            'nullable'  => false,
        ),
        'Message ID'
    )
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        64,
        array(
            'nullable'  => false,
        ),
        'Status'
    )
    ->addColumn(
        'sent_at',
        Varien_Db_Ddl_Table::TYPE_DATETIME,
        null,
        array(
            'nullable'  => true,
            'default'   => null,
        ),
        'Date Sent'
    )
    ->addColumn(
        'recipient',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        1024,
        array(
            'nullable'  => false,
        ),
        'Recipient'
    )
    ->addColumn(
        'sender',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        1024,
        array(
            'nullable'  => false,
        ),
        'Sender'
    )
    ->addColumn(
        'subject',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        null,
        array(
            'nullable'  => false,
        ),
        'Subject'
    )
    ->addColumn(
        'total_opens',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'nullable'  => false,
        ),
        'Total Opens'
    )
    ->addColumn(
        'total_clicks',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'nullable'  => false,
        ),
        'Total Clicks'
    )
    ->addColumn(
        'can_be_resent',
        Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        null,
        array(
            'nullable'  => false,
        ),
        'Can be Resent'
    )
    ->addColumn(
        'scope',
        Varien_Db_Ddl_Table::TYPE_VARCHAR,
        8,
        array(
            'nullable'  => false,
        ),
        'Scope'
    )
    ->addColumn(
        'scope_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        11,
        array(
            'nullable'  => false,
        ),
        'Scope ID'
    );

$connection->createTable($emailTable);

$this->endSetup();
