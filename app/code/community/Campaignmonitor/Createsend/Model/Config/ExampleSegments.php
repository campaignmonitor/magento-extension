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

class Campaignmonitor_Createsend_Model_Config_ExampleSegments
{
    /**
     * Returns Example Segments to be created in Campaign Monitor
     *
     * The keys of the return array correspond to the custom field(s) that are required for
     * creating the example segment.
     *
     * The array keys should be in the format: [<Rule name> :] <RequireField1> [, <RequiredField2> [...]]
     * The Rule name along with the colon (:) can be omitted if the required fields combination is unique.
     *
     * The required fields will be displayed to the user along with the error message
     * when the segment creation results in a CODE_INVALID_SEGMENT_RULES error
     * (happens when custom fields are non-existent) to let the user know
     * which fields are missing.
     *
     * See Campaign Monitor API for segment definition format:
     *
     * @link https://www.campaignmonitor.com/api/segments/
     *
     * @return array
     */
    public function getExampleSegments()
    {
        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        /** @var Campaignmonitor_Createsend_Model_Config_CustomerAttributes $customerAttributes */
        $customerAttributes = Mage::getSingleton('createsend/config_customerAttributes');

        $cmHasCustomerAccount = $api->formatCustomFieldName(
            $customerAttributes->getCustomFieldName('FONTIS-has-account', true)
        );
        $cmAverageOrderValue = $api->formatCustomFieldName(
            $customerAttributes->getCustomFieldName('FONTIS-sales-average-order-value', true)
        );
        $cmTotalNumberOfOrders = $api->formatCustomFieldName(
            $customerAttributes->getCustomFieldName('FONTIS-sales-total-number-of-orders', true)
        );
        $cmTotalOrderValue = $api->formatCustomFieldName(
            $customerAttributes->getCustomFieldName('FONTIS-sales-total-order-value', true)
        );
        $cmCustomerGender =  $api->formatCustomFieldName(
            $customerAttributes->getCustomFieldName('gender', true)
        );
        $cmWishlistItemCount = $api->formatCustomFieldName(
            $customerAttributes->getCustomFieldName('FONTIS-number-of-wishlist-items', true)
        );

        $sampleSegments = array(
            "All subscribers: $cmHasCustomerAccount" => array(
                'Title' => 'All subscribers',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmHasCustomerAccount,
                                'Clause'    => 'EQUALS Yes'
                            )
                        )
                    )
                )
            ),
            "Big spenders: $cmAverageOrderValue" => array(
                'Title' => 'Big spenders',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmAverageOrderValue,
                                'Clause'    => 'GREATER_THAN_OR_EQUAL 500'
                            )
                        )
                    )
                )
            ),
            "Frequent Buyers: $cmTotalNumberOfOrders" => array(
                'Title' => 'Frequent Buyers',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmTotalNumberOfOrders,
                                'Clause'    => 'GREATER_THAN_OR_EQUAL 5'
                            )
                        )
                    )
                )
            ),
            "VIPs: $cmTotalNumberOfOrders, $cmTotalOrderValue" => array(
                'Title' => 'VIPs',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmTotalNumberOfOrders,
                                'Clause'    => 'GREATER_THAN_OR_EQUAL 5'
                            )
                        )
                    ),
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmTotalOrderValue,
                                'Clause'    => 'GREATER_THAN_OR_EQUAL 500'
                            )
                        )
                    )
                )
            ),
            "Subscribers that haven’t purchased: $cmHasCustomerAccount, $cmTotalNumberOfOrders" => array(
                'Title' => 'Subscribers that haven’t purchased',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmHasCustomerAccount,
                                'Clause'    => 'EQUALS Yes'
                            )
                        )
                    ),
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmTotalNumberOfOrders,
                                'Clause'    => 'EQUALS 0'
                            )
                        )
                    )
                )
            ),
            "First time customers: $cmTotalNumberOfOrders" => array(
                'Title' => 'First time customers',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmTotalNumberOfOrders,
                                'Clause'    => 'EQUALS 1'
                            )
                        )
                    )
                )
            ),
            "All customers: $cmTotalNumberOfOrders" => array(
                'Title' => 'All customers',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmTotalNumberOfOrders,
                                'Clause'    => 'GREATER_THAN_OR_EQUAL 1'
                            )
                        )
                    )
                )
            ),
            "Customers with a wishlist: $cmWishlistItemCount" => array(
                'Title' => 'Customers with a wishlist',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmWishlistItemCount,
                                'Clause'    => 'GREATER_THAN_OR_EQUAL 1'
                            )
                        )
                    )
                )
            ),
            "Males: $cmCustomerGender" => array(
                'Title' => 'Males',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmCustomerGender,
                                'Clause'    => 'EQUALS Male'
                            )
                        )
                    )
                )
            ),
            "Females: $cmCustomerGender" => array(
                'Title' => 'Females',
                'RuleGroups' => array(
                    array(
                        'Rules' => array(
                            array(
                                'RuleType'  => $cmCustomerGender,
                                'Clause'    => 'EQUALS Female'
                            )
                        )
                    )
                )
            ),
        );

        return $sampleSegments;
    }
}
