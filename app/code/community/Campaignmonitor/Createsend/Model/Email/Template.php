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

class Campaignmonitor_Createsend_Model_Email_Template extends Mage_Core_Model_Email_Template
{
    const ERR_CANNOT_SEND_EMAIL = 'Cannot send email via Campaign Monitor Transactional API: %s';

    /**
     * Overrides the Mage_Core_Model_Email_Template send() method to send email via Campaign Monitor Transactional API.
     * Some of the code are from the original parent send() method. Unused code from the original method are
     * commented out for reference.
     *
     * @param array|string $email E-mail(s)
     * @param array|string|null $name receiver name(s)
     * @param array $variables Template variables
     * @return bool
     * @throws Exception when the letter cannot be sent because it it not valid
     */
    public function send($email, $name = null, array $variables = array())
    {
        /** @var $helper Campaignmonitor_Createsend_Helper_Data */
        $helper = Mage::helper("createsend");

        $storeId = Mage::app()->getStore()->getStoreId();

        // Only send emails through Campaign Monitor is the admin has selected to do so
        $transactionalEmailEnabled = $helper->isTransactionalEmailsEnabled($storeId);
        if ($transactionalEmailEnabled == false) {
            return parent::send($email, $name, $variables);
        }

        if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array) $email);
        $names = is_array($name) ? $name : (array)$name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);

        // TODO: Partition into logical groups such as "Orders", "Shipments"
        $group = 'Magento';

        $emailData = array(
            'Subject'       => null,
            'From'          => null,
            'ReplyTo'       => null,
            'To'            => null,
            'CC'            => null,
            'BCC'           => null,
            'Html'          => '',
            'Text'          => '',
            'Attachments'   => null,
            'TrackOpens'    => true,
            'TrackClicks'   => true,
            'InlineCSS'     => true,
            'Group'         => $group   // Used for grouping email for reporting
        );

        $setReturnPath = Mage::getStoreConfig(self::XML_PATH_SENDING_SET_RETURN_PATH);
        switch ($setReturnPath) {
            case 1:
                $returnPathEmail = $this->getSenderEmail();
                break;
            case 2:
                $returnPathEmail = Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
                break;
            default:
                $returnPathEmail = null;
                break;
        }

        if ($returnPathEmail !== null) {
            $emailData['ReplyTo'] = $returnPathEmail;
        }

        $recipients = array();
        foreach ($emails as $key => $email) {
            $recipients[] = $names[$key] . '<' . $email . '>';
        }

        $emailData['To'] = $recipients;

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);

        if ($this->isPlain()) {
            $emailData['Text'] = $text;
        } else {
            $emailData['Html'] = $text;
        }

        $emailData['Subject'] = $this->getProcessedTemplateSubject($variables);
        $emailData['From'] = '"' . $this->getSenderName() . '" <' . $this->getSenderEmail() .'>';

        try {
            $this->_send($emailData, $variables);
            $this->_mail = null;
        } catch (Exception $e) {
            $this->_mail = null;
            $helper->log(sprintf(self::ERR_CANNOT_SEND_EMAIL, $e), Zend_Log::ERR);
            return false;
        }

        return true;
    }

    /**
     * Sends a 'classic email' using the Campaign Monitor Transactional Email API.
     *
     * @link https://www.campaignmonitor.com/api/transactional/#send_a_basic_email
     *
     * Expects an array of the form:
     * <pre>
     *  {
     *      "Subject": "Thanks for signing up to web app 123",
     *      "From": "Mike Smith <mike@webapp123.com>",
     *      "ReplyTo": "support@webapp123.com",
     *      "To": [
     *          "Joe Smith <joesmith@example.com>",
     *          "jamesmith@example.com"
     *      ],
     *      "CC": [
     *          "Joe Smith <joesmith@example.com>"
     *      ],
     *      "BCC": null,
     *      "Html": "",
     *      "Text": "",
     *      "Attachments": [
     *          {
     *              "Type": "application/pdf",
     *              "Name": "Invoice.pdf",
     *              "Content": "base64encoded"
     *          }
     *      ],
     *      "TrackOpens": true,
     *      "TrackClicks": true,
     *      "InlineCSS": true,
     *      "Group": "Password Reset",
     *      "AddRecipientsToListID": "62eaaa0338245ca68e5e93daa6f591e9"
     *  }
     * </pre>
     *
     * @param array $emailData Data to send
     * @param array $variables Template variables
     * @throws Exception
     */
    protected function _send(array $emailData, array $variables = array())
    {
        /** @var Campaignmonitor_Createsend_Helper_Data $helper */
        $helper = Mage::helper('createsend');

        /** @var Campaignmonitor_Createsend_Model_Api $api */
        $api = Mage::getModel('createsend/api');

        // Use current store's configuration to send the email
        $storeId = Mage::app()->getStore()->getStoreId();

        if ($storeId) {
            $scope = 'stores';
            $scopeId = $storeId;
        } else {
            $scope = 'default';
            $scopeId = 0;
        }

        $result = $api->call(
            Zend_Http_Client::POST,
            'transactional/classicEmail/send',
            $emailData,
            array(
                'clientID' => $helper->getClientId($storeId)
            ),
            $scope,
            $scopeId
        );

        if (!$result['success']) {
            throw new Exception($result['data']['Message']);
        }
    }
}
