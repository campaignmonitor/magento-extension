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

class Campaignmonitor_Createsend_Block_Adminhtml_Email_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('createsend_email_grid');
        $this->setDefaultSort('sent_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareColumns()
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        $this->addColumn('sent_at',
            array(
                'header'    => $helper->__('Sent At'),
                'type'      => 'datetime',
                'index'     => 'sent_at'
            )
        );

        /** @var Campaignmonitor_Createsend_Model_Email $email */
        $email = Mage::getSingleton('createsend/email');

        $this->addColumn("status",
            array(
                'header'    => $helper->__('Status'),
                'index'     => 'status',
                'type'      => 'options',
                'options'   => array(
                    $email::STATUS_SENT      => $helper->__($email::STATUS_SENT),
                    $email::STATUS_ACCEPTED  => $helper->__($email::STATUS_ACCEPTED),
                    $email::STATUS_DELIVERED => $helper->__($email::STATUS_DELIVERED),
                    $email::STATUS_BOUNCED   => $helper->__($email::STATUS_BOUNCED),
                    $email::STATUS_SPAM      => $helper->__($email::STATUS_SPAM),
                ),
            )
        );

        $this->addColumn('subject',
            array(
                'header'    => $helper->__('Subject'),
                'index'     => 'subject'
            )
        );

        $this->addColumn('recipient',
            array(
                'header'    => $helper->__('Recipient'),
                'index'     => 'recipient'
            )
        );

        if ((Mage::getSingleton('customer/config_share')->isWebsiteScope())
            && Mage::app()->getRequest()->getUserParam('id')) {

            $this->addColumn('website',
                array(
                    'header' => $helper->__('Website'),
                    'width' => '100px',
                    'sortable' => false,
                    'index' => 'website',
                    'type' => 'options',
                    'options' => Mage::getModel('core/website')->getCollection()->toOptionHash(),
                )
            );
        }

        if (Mage::getSingleton('admin/session')->isAllowed('adminhtml/createsend_email/view')) {
            $request = Mage::app()->getRequest();
            $customerId = $request->getUserParam('id');
            if (!$customerId) {
                $customerId = 0;
            }

            $this->addColumn('action',
                array(
                    'header'    => $helper->__('Action'),
                    'width'     => '50px',
                    'type'      => 'action',
                    'getter'    => 'getEmailId',
                    'actions'   => array(
                        array(
                            'caption'       => $helper->__('View'),
                            'url'           => array(
                                'base'      => 'adminhtml/createsend_email/view',
                                'params'    => array(
                                    'customer_id'   => $customerId
                                )
                            ),
                            'field'         => 'email_id',
                            'data-column'   => 'action',
                        )
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'is_system' => true,
                )
            );
        }

        return parent::_prepareColumns();
    }

    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            $cond = $column->getFilter()->getCondition();

            if ($column->getId() == 'website') {
                if (!empty($cond)) {
                    foreach ($cond as $websiteId) {
                        $website = Mage::app()->getWebsite($websiteId);

                        $collection = $this->getCollection();
                        $conn = $collection->getConnection();

                        $collection->getSelect()->where(
                            new Zend_Db_Expr(
                                $conn->quoteInto('(scope=?', 'websites')
                                . ' AND ' .
                                $conn->quoteInto('scope_id=?)', $websiteId)
                                . ' OR ' .
                                $conn->quoteInto('(scope=?', 'stores')
                                . ' AND ' .
                                $conn->quoteInto('scope_id in (?))', $website->getStoreIds())
                            )
                        );
                    }
                }

                return $this;
            }
        }

        return parent::_addColumnFilterToCollection($column);
    }

    protected function _prepareCollection()
    {
        /** @var Campaignmonitor_Createsend_Model_Resource_Email_Collection $collection */
        $collection = Mage::getModel('createsend/email')->getCollection();

        $request = Mage::app()->getRequest();
        $website = $request->getUserParam('website');
        $store   = $request->getUserParam('store');

        if ($customerId = $request->getUserParam('id')) {
            if ($customer = Mage::getSingleton('customer/customer')->load($customerId)) {
                $collection = $collection->addFieldToFilter(
                    'recipient',
                    array(
                        'like'  => '%' . $customer->getEmail() . '%'
                    )
                );
            }
        } else {
            list($scope, $scopeId) = Mage::getSingleton('createsend/config_scope')->_getScope($website, $store);
            $collection->addFieldToFilter('scope', $scope)->addFieldToFilter('scope_id', $scopeId);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/createsend_email/grid', array('_current'=>true));
    }
}
