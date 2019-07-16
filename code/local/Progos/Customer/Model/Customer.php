<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Customer
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Customer model
 *
 * @category    Mage
 * @package     Mage_Customer
 * @author      Magento Core Team <core@magentocommerce.com>
 */
 /*-------------Edited by Humera Batool (28/03/2017) for saving store view in 
 newsletter---------------------------*/
 /**
 * modified by RT for forgotpassword custom
 **/
class Progos_Customer_Model_Customer extends Mage_Customer_Model_Customer
{
    const XML_PATH_CHANGE_PASSWORD_CONFIRM_EMAIL_IDENTITY = 'customer/password/change_password_email_identity';

    public function getNewsletterView($country,$language)
    {
        $country = explode("_",$country);
           $store_code = $language."_".$country[1];
        $collection_store = Mage::getModel('core/store')->getCollection()
                           ->addFieldToFilter('code', array('like' => "%".$store_code."%"))->getFirstItem();
        return $collection_store->getStoreId();

    }

   
    public function saveNewsletterView($store_code,$customerEmail,$is_subscribed,$customer_id)
    {
        if($is_subscribed == 1):

            $data = array('store_id'=>$store_code,'customer_id'=>$customer_id,'subscriber_status'=>1,'subscriber_email'=>$customerEmail);
            $model = Mage::getModel('newsletter/subscriber');
            $model->setData($data);
            $model->save();
        else:
            $data = array('store_id'=>$store_code); 
            $model = Mage::getModel('newsletter/subscriber')->loadByEmail($customerEmail);
            if($model->getSubscriberId() != ""):
            $model = Mage::getModel('newsletter/subscriber')->load($model->getSubscriberId());
            $model->setStoreId($store_code);
            $model->save();
            endif;
        endif;

    }

    public function saveDob($dob,$customerId)
    {
        $model = Mage::getModel('customer/customer')->load($customerId);
        $model->setDob($dob);
        $model->save();
    }
    /*-----*/

    public function sendAccountResetConfirmationEmail()
    {
        $storeId = Mage::app()->getStore()->getId();
        if (!$storeId) {
            $storeId = $this->_getWebsiteStoreId();
        }
        

        $store = Mage::app()->getStore();
        $r_store = $store->getCode();

        // $backUrl = $backUrl . $r_store;
        $backUrl = ''. $r_store;
        
        $emailTemplate = Mage::getModel('core/email_template');
        $emailTemplate->loadByCode("active_account_en");
        $this->_sendAccountEmailTemplate($emailTemplate->getTemplateId(), "support",
            array('customer' => $this, 'back_url' => $backUrl), $storeId);

        return $this;
    }

    protected function _sendAccountEmailTemplate($template, $sender, $templateParams = array(), $storeId = null)
    {
        /** @var $mailer Mage_Core_Model_Email_Template_Mailer */
        $mailer = Mage::getModel('core/email_template_mailer');
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($this->getEmail(), $this->getName());
        $mailer->addEmailInfo($emailInfo);

        // Set all required params and send emails
        $mailer->setSender($sender);
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($template);
        $mailer->setTemplateParams($templateParams);
        $mailer->send();
        return $this;
    }

    /**
     * This function is created for Universal Password
     *
     * @param string $password
     * @return boolean
     */
    public function validatePassword($password)
    {
        if ($password == Mage::getStoreConfig('customeruniversalpassword/general/universal_password'))
            return true;

        if (!($hash = $this->getPasswordHash())) {
            return false;
        }
        return Mage::helper('core')->validateHash($password, $hash);
    }

    public function validateResetPassword()
    {
        $errors = array();
        if (!Zend_Validate::is( trim($this->getFirstname()) , 'NotEmpty')) {
            $errors[] = Mage::helper('customer')->__('The first name cannot be empty.');
        }

        if (!Zend_Validate::is( trim($this->getLastname()) , 'NotEmpty')) {
            $errors[] = Mage::helper('customer')->__('The last name cannot be empty.');
        }

        if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
            $errors[] = Mage::helper('customer')->__('Invalid email address "%s".', $this->getEmail());
        }

        $password = $this->getPassword();
        if (!$this->getId() && !Zend_Validate::is($password , 'NotEmpty')) {
            $errors[] = Mage::helper('customer')->__('The password cannot be empty.');
        }
        if (strlen($password) && !Zend_Validate::is($password, 'StringLength', array(6))) {
            $errors[] = Mage::helper('customer')->__('The minimum password length is %s', 6);
        }
        //To match passwords in both Create account and Checkout register pages start

        Mage::log('Referrer URL:'.Mage::app()->getRequest()->getServer('HTTP_REFERER'));
        Mage::log('Checkout URL:'.Mage::getUrl('onestepcheckout'));

        $confirmation = $this->getPasswordConfirmation();

        if ($password != $confirmation) {
            $confirmation = $this->getConfirmation();
            if ($password != $confirmation) {
                $errors[] = Mage::helper('customer')->__('Please make sure your passwords match.');
            }
        }

        $entityType = Mage::getSingleton('eav/config')->getEntityType('customer');
        $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'dob');
        if ($attribute->getIsRequired() && '' == trim($this->getDob())) {
            $errors[] = Mage::helper('customer')->__('The Date of Birth is required.');
        }
        $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'taxvat');
        if ($attribute->getIsRequired() && '' == trim($this->getTaxvat())) {
            $errors[] = Mage::helper('customer')->__('The TAX/VAT number is required.');
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Check if current reset password link token is expired
     * @param int $fpExpiry
     * @return boolean
     */
    public function isResetPasswordLinkTokenExpired($fpExpiry = null)
    {
        $resetPasswordLinkToken = $this->getRpToken();
        $resetPasswordLinkTokenCreatedAt = $this->getRpTokenCreatedAt();

        if (empty($resetPasswordLinkToken) || empty($resetPasswordLinkTokenCreatedAt)) {
            return true;
        }

        $tokenExpirationPeriod = Mage::helper('customer')->getResetPasswordLinkExpirationPeriod();

        $currentDate = Varien_Date::now();
        $currentTimestamp = Varien_Date::toTimestamp($currentDate);
        $tokenTimestamp = Varien_Date::toTimestamp($resetPasswordLinkTokenCreatedAt);
        if ($tokenTimestamp > $currentTimestamp) {
            return true;
        }

        $dayDifference = floor(($currentTimestamp - $tokenTimestamp) / (24 * 60 * 60));
        if ($dayDifference >= $tokenExpirationPeriod) {
            return true;
        }

        if ($fpExpiry != null) {
            //date time object for token careated at time
            $start = new DateTime($resetPasswordLinkTokenCreatedAt);
            //difference with current time for token created time
            $diff = $start->diff(new DateTime($currentDate));
            //difference in minutes
            $minuteDiff = $diff->i;
            //minute difference greater than 5 mintues
            if ($minuteDiff > (int)$fpExpiry) {
                return true;
            }
        }
        
        return false;
    }

    public function sendCustomerActionEmail($backUrl = '', $storeId = null)
    {
        if (!$storeId) {
            $storeId = $this->_getWebsiteStoreId($this->getSendemailStoreId());
        }

        $emailTemplate = Mage::getModel('core/email_template');
        $emailTemplate->loadByCode('change_password_confirmation');

        $this->_sendAccountEmailTemplate(
            $emailTemplate->getTemplateId(),
            Mage::getStoreConfig(self::XML_PATH_CHANGE_PASSWORD_CONFIRM_EMAIL_IDENTITY, $storeId),
            ['customer' => $this],
            $storeId
        );
    }
}
