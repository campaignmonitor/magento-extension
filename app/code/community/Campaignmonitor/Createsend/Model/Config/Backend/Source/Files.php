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

class Campaignmonitor_Createsend_Model_Config_Backend_Source_Files extends Mage_Core_Model_Config_Data
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $directory = Mage::getBaseDir();
        $directory .= DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log';

        if (is_dir( $directory )) {
            $files = array_diff( scandir( $directory ), array( '..', '.' ) );

            if (!empty( $files )) {

                $options = array();
                foreach ($files as $filename) {
                    $options[] = array( 'value' => $filename, 'label' => Mage::helper( 'adminhtml' )->__( $filename ) );
                }

                return $options;
            }
        }


        return array(
            array( 'value' => 0, 'label' => Mage::helper( 'adminhtml' )->__( 'No log files found' ) ),
        );


    }

	/**
	 * @param array $arrAttributes
	 *
	 * @return array
	 */
    public function toArray(array $arrAttributes = array())
    {

        $directory = Mage::getBaseDir();
        $directory .= DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log';

        if (is_dir( $directory )) {
            $files = array_diff( scandir( $directory ), array( '..', '.' ) );

            if (!empty( $files )) {

                $options = array();
                foreach ($files as $filename) {
                    $options[] = Mage::helper( 'adminhtml' )->__( $filename );
                }

                return $options;
            }
        }

        return array(
            0 => Mage::helper('adminhtml')->__('One'),
            1 => Mage::helper('adminhtml')->__('Two'),
        );
    }
}