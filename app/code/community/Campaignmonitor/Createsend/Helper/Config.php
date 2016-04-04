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

class Campaignmonitor_Createsend_Helper_Config extends Mage_Core_Helper_Abstract
{
    const FLAG_STATUS_NEW           = 'new';
    const FLAG_STATUS_PROCESSING    = 'processing';
    const FLAG_STATUS_DONE          = 'done';

    const FLAG_PROCESS_RUNNING      = 'running';
    const FLAG_PROCESS_STOPPED      = 'stopped';

    const INDEX_INITIAL_SYNC        = 'initialSync';
    const INDEX_CUSTOM_FIELDS       = 'customFields';
    const INDEX_EXAMPLE_SEGMENTS    = 'exampleSegments';

    /**
     * Adds the default customer and product attributes to the magento configuration setting for this scope.
     * Returns the number of attributes added.
     *
     * @param string $scope
     * @param int $scopeId
     * @return int
     */
    function createDefaultCustomFields($scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Config_DefaultCustomFields $defaultSource */
        $defaultSource = Mage::getSingleton('createsend/config_defaultCustomFields');

        $linkedAttributes = @unserialize(
            $helper->getScopedConfig(
                Campaignmonitor_Createsend_Helper_Data::XML_PATH_M_TO_CM_ATTRIBUTES,
                $scope,
                $scopeId
            )
        );
        $defaultCustomerAttributes = $defaultSource->getDefaultCustomerAttributes();
        $newLinkedAttributes = $this->addDefaultAttributes($linkedAttributes, $defaultCustomerAttributes);
        Mage::getConfig()->saveConfig(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_M_TO_CM_ATTRIBUTES,
            @serialize($newLinkedAttributes),
            $scope,
            $scopeId
        );

        $productAttributes = @unserialize(
            $helper->getScopedConfig(
                Campaignmonitor_Createsend_Helper_Data::XML_PATH_WISHLIST_PRODUCT_ATTRIBUTES,
                $scope,
                $scopeId
            )
        );
        $defaultProductAttributes = $defaultSource->getDefaultProductAttributes();
        $newProductAttributes = $this->addDefaultAttributes($productAttributes, $defaultProductAttributes);
        Mage::getConfig()->saveConfig(
            Campaignmonitor_Createsend_Helper_Data::XML_PATH_WISHLIST_PRODUCT_ATTRIBUTES,
            @serialize($newProductAttributes),
            $scope,
            $scopeId
        );

        $oldCount = count($linkedAttributes) + count($productAttributes);
        $newCount = count($newLinkedAttributes) + count($newProductAttributes);

        return ($newCount - $oldCount);
    }

    /**
     * Returns the duplicate custom attributes (if any).
     * Returns an empty array if no duplicate custom attributes found.
     *
     * @param string $configPath The system configuration path for the attributes
     * @param string $scope
     * @param int $scopeId
     * @return array
     */
    function getDuplicateAttributes($configPath, $scope, $scopeId)
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $duplicateAttributes = array();

        $customerAttributes = @unserialize(
            $helper->getScopedConfig(
                $configPath,
                $scope,
                $scopeId
            )
        );

        $usedAttributes = array();
        foreach ($customerAttributes as $attr) {
            $value = $attr['magento'];

            if (in_array($value, $usedAttributes)) {
                // Already used, add it to the duplicate list
                $duplicateAttributes[] = $value;
            } else {
                $usedAttributes[] = $value;
            }
        }

        return $duplicateAttributes;
    }

    /**
     * Adds values from $defaultAttributes into $attributes if not yet in array
     *
     * @param array $attributes
     * @param array $defaultAttributes
     * @return array
     */
    protected function addDefaultAttributes($attributes, $defaultAttributes)
    {
        if (!is_array($attributes)) {
            $attributes = array();
        }

        foreach ($defaultAttributes as $attr) {
            $addAttribute = array('magento' => $attr);
            if (in_array($addAttribute, $attributes) === FALSE) {
                $attributes[] = $addAttribute;
            }
        }

        return $attributes;
    }

    /**
     * Loads the flag given the flag class.
     *
     * @param string $flagClass
     * @return Mage_Core_Model_Flag
     */
    protected function loadFlag($flagClass)
    {
        /** @var Mage_Core_Model_Flag $flagModel */
        $flagModel = Mage::getModel($flagClass);
        $flagModel->loadSelf();

        return $flagModel;
    }

    /**
     * Adds data to the flag with an index key. Existing data for the key will be overwritten.
     * Data for other keys will remain untouched.
     *
     * @param string $flagClass
     * @param string $key
     * @param string $listData
     * @throws Exception
     */
    public function addFlagData($flagClass, $key, $listData)
    {
        $flagModel = $this->loadFlag($flagClass);

        /** @var array $flagData */
        $flagData = $flagModel->getFlagData();
        if ($flagData === null) {
            $flagData = array();
        }
        $flagData[$key] = $listData;

        $flagModel->setFlagData($flagData)->save();
    }

    /**
     * Retrieves flag data given the flag class and the index key.
     *
     * @param string $flagClass
     * @param string $key
     * @return mixed
     */
    public function getFlagData($flagClass, $key)
    {
        $flagModel = $this->loadFlag($flagClass);

        $flagData = $flagModel->getFlagData();

        if (is_array($flagData) && array_key_exists($key, $flagData)) {
            return $flagData[$key];
        } else {
            return FALSE;
        }
    }
}
