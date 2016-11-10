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

require_once 'abstract.php';

/**
 * Download email data from Campaign Monitor for viewing in the Magento admin panel
 * and purge old email data from Magento database.
 *
 * To use this script, go to:
 *
 *      ``System -> Configuration -> Campaign Monitor -> General -> Advanced``
 *
 * and set ``Use Magento cron`` to ``No``.
 *
 * Then add an entry in your Unix crontab to run this script every hour, or use an interval of your choice.
 *
 */
class Mage_Shell_Campaignmonitor_Email extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        /** @var array $tasks */
        $tasks = array();
        $tasks[] = Mage::getModel('createsend/email_cron');

        /** @var Campaignmonitor_Createsend_Model_Cron $task */
        foreach ($tasks as $task) {
            $task->runJob();
        }
    }
}

$shell = new Mage_Shell_Campaignmonitor_Email();
$shell->run();
