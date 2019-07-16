<?php

/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

/**
 * Class MageWorx_OrdersSurcharge_Model_Mail
 * @method int getStoreId()
 * @method MageWorx_OrdersSurcharge_Model_Mail setStoreId(int $id);
 * @method MageWorx_OrdersSurcharge_Model_Mail setOrder(Mage_Sales_Model_Order $order)
 * @method Mage_Sales_Model_Order|null getOrder()
 * @method MageWorx_OrdersSurcharge_Model_Mail setSurcharge(MageWorx_OrdersSurcharge_Model_Surcharge $surcharge)
 * @method MageWorx_OrdersSurcharge_Model_Surcharge|null getSurcharge()
 */
class MageWorx_OrdersSurcharge_Model_Mail extends Mage_Core_Model_Abstract
{

    const XML_PATH_EMAIL_TEMPLATE               = 'mageworx_ordersmanagement/orderssurcharge/template';
    const XML_PATH_EMAIL_GUEST_TEMPLATE         = 'mageworx_ordersmanagement/orderssurcharge/guest_template';
    const XML_PATH_EMAIL_IDENTITY               = 'mageworx_ordersmanagement/orderssurcharge/identity';
    const XML_PATH_EMAIL_COPY_TO                = 'mageworx_ordersmanagement/orderssurcharge/copy_to';
    const XML_PATH_EMAIL_COPY_METHOD            = 'mageworx_ordersmanagement/orderssurcharge/copy_method';
    const XML_PATH_EMAIL_ENABLED                = 'mageworx_ordersmanagement/orderssurcharge/email_enabled';

    const TEMPLATE_CODE                         = 'New Payment Link';
    const GUEST_TEMPLATE_CODE                   = 'New Payment Link (Guest)';

    /**
     * Send email with shipment data
     *
     * @param boolean $notifyCustomer
     * @param string $comment
     * @return Mage_Sales_Model_Order_Shipment
     * @throws Exception
     */
    public function sendEmail($notifyCustomer = true, $comment = '')
    {
        $helper = $this->getHelper();
        if (!$helper->isEnabled() || !$this->isEmailsEnabled()) {
            return $this;
        }

        $order = $this->getOrder();
        if (!($order instanceof Mage_Sales_Model_Order)) {
            return $this;
        }

        $storeId = $order->getStore()->getId();
        $this->setStoreId($storeId);

        $surcharge = $this->getSurcharge();
        if (!($surcharge instanceof MageWorx_OrdersSurcharge_Model_Surcharge)) {
            return $this;
        }

        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);

        // Check if at least one recepient is found
        if (!$notifyCustomer && !$copyTo) {
            return $this;
        }

        // Retrieve corresponding email template id and customer name
        if ($order->getCustomerIsGuest()) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
            if ($templateId == str_ireplace('/', '_', self::XML_PATH_EMAIL_GUEST_TEMPLATE)) {
                $templateModel = Mage::getModel('core/email_template')->loadByCode(self::GUEST_TEMPLATE_CODE);
                $templateId = $templateModel->getId();
            }
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
            if ($templateId == str_ireplace('/', '_', self::XML_PATH_EMAIL_TEMPLATE)) {
                $templateModel = Mage::getModel('core/email_template')->loadByCode(self::TEMPLATE_CODE);
                $templateId = $templateModel->getId();
            }
            $customerName = $order->getCustomerName();
        }

        if (!$templateId) {
            throw new Exception('Empty surcharge template id');
        }

        /** @var Mage_Core_Model_Email_Template_Mailer $mailer */
        $mailer = Mage::getModel('core/email_template_mailer');
        if ($notifyCustomer) {
            $emailInfo = Mage::getModel('core/email_info');
            $emailInfo->addTo($order->getCustomerEmail(), $customerName);
            if ($copyTo && $copyMethod == 'bcc') {
                // Add bcc to customer email
                foreach ($copyTo as $email) {
                    $emailInfo->addBcc($email);
                }
            }
            $mailer->addEmailInfo($emailInfo);
        }

        // Email copies are sent as separated emails if their copy method is 'copy' or a customer should not be notified
        if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
            foreach ($copyTo as $email) {
                /** @var Mage_Core_Model_Email_Info $emailInfo */
                $emailInfo = Mage::getModel('core/email_info');
                $emailInfo->addTo($email);
                $mailer->addEmailInfo($emailInfo);
            }
        }

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $surchargeViewUrl = Mage::getSingleton('core/url')->getUrl(
            'sales/order/view',
            array('order_id' => $surcharge->getParentOrderId())
        );
        $surchargeTotalDueFormatted = Mage::helper('core')->formatPrice($surcharge->getBaseTotalDue());
        $mailer->setTemplateParams(array(
                'order' => $order,
                'comment' => $comment,
                'surcharge' => $surcharge,
                'name' => $customerName,
                'title' => $helper->__('New Payment Link'),
                'surchargeLink' => $surchargeViewUrl,
                'surchargeTotalDue' => $surchargeTotalDueFormatted
            )
        );
        $mailer->send();

        return $this;
    }

    protected function _getEmails($configPath)
    {
        $data = Mage::getStoreConfig($configPath, $this->getStoreId());
        if (!empty($data)) {
            return explode(',', $data);
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function isEmailsEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_EMAIL_ENABLED);
    }

    /**
     * @return MageWorx_OrdersSurcharge_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('mageworx_orderssurcharge');
    }
}