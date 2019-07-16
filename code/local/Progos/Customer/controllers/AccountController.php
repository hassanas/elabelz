<?php 
/**
 * Customer account controller
 *
 * @category   local overwride controller
 * @package    Progos_Customer
 * @author     Azhar Farooq <az.fq.jh@gmail.com>
 */
require_once 'Mage/Customer/controllers/AccountController.php';
class Progos_Customer_AccountController extends Mage_Customer_AccountController
{

    public function confirmPassAction()
    {
        $id = $this->getRequest()->getParam('id', false);
        $token = $this->getRequest()->getParam('key', false);
        $backUrl = $this->getRequest()->getParam('back_url', false);

        if (empty($id) || empty($token)) {
            $this->_getSession()->addError($this->_getHelper('customer')->__('Bad Request'));
            $this->_redirect('*/*/');
            return false;
        }

        $websiteId = Mage::app()->getWebsite()->getId();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)
            ->load($id);

        if ($customer->getPswdConfirmToken()) {

            if (now() > $customer->getPswdConfirmTokenExpiry()) {
                $this->_getSession()->addError($this->_getHelper('customer')->__('Confirmation token has expired!'));
                $this->_redirect('*/*/');
                return;
            }

            if ($token != $customer->getPswdConfirmToken()) {
                $this->_getSession()->addError($this->_getHelper('customer')->__('Wrong confirmation key!'));
                $this->_redirect('*/*/');
                return;
            }
            try {
                //set password with new one and remove token and expiry time
                $customer->setPassword($customer->getCredentials())
                    ->setPswdConfirmToken('')
                    ->setPswdConfirmTokenExpiry('')
                    ->save();
                $this->_getSession()
                    ->addSuccess($this->_getHelper('customer')->__('Your Password has been Changed Successfully'));
                $this->_redirect('*/*/', ['_secure' => true]);
                return;
            } catch (Exception $ex) {
                $this->_getSession()->addError($ex->getMessage());
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->_getSession()->addError($this->_getHelper('customer')->__('Unable to perform the action!'));
        $this->_redirect('*/*/');
        return;
    }
    /**
     * added by RT
     * Forgot customer password action
     */
    public function forgotPasswordPostAction()
    {
        $email = (string) $this->getRequest()->getPost('email');
        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                $this->_getSession()->addError($this->__('Invalid email address.'));
                $this->_redirect('*/*/forgotpassword');
                return;
            }

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($customer->getId()) {
                try {
                    $fpExpiry = (int)Mage::getStoreConfig('customer/password/forgot_password_request_time');
                    if ($fpExpiry == null) {
                        $fpExpiry = 5;
                    }
                    if (!$customer->isResetPasswordLinkTokenExpired($fpExpiry)) {
                        $this->_getSession()->addError($this->_getHelper('customer')->__('Email with link to reset password is already sent! Please try after %d minutes', $fpExpiry));
                        $this->_redirect('*/*/');
                        return;
                    }
                    $newResetPasswordLinkToken =  $this->_getHelper('customer')->generateResetPasswordLinkToken();
                    $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                    $customer->sendPasswordResetConfirmationEmail();
                } catch (Exception $exception) {
                    $this->_getSession()->addError($exception->getMessage());
                    $this->_redirect('*/*/forgotpassword');
                    return;
                }
            }
            $this->_getSession()
                ->addSuccess( $this->_getHelper('customer')
                ->__('If there is an account associated with %s you will receive an email with a link to reset your password.',
                    $this->_getHelper('customer')->escapeHtml($email)));
            $this->_redirect('*/*/');
            return;
        } else {
            $this->_getSession()->addError($this->__('Please enter your email.'));
            $this->_redirect('*/*/forgotpassword');
            return;
        }
    }
	 /**
     * Success Registration
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Mage_Customer_AccountController
     */
    protected function _successProcessRegistration(Mage_Customer_Model_Customer $customer)
    {
        $session = $this->_getSession();
        if ($customer->isConfirmationRequired()) {
            /** @var $app Mage_Core_Model_App */
            $app = $this->_getApp();
            /** @var $store  Mage_Core_Model_Store*/
            $store = $app->getStore();
            $customer->sendNewAccountEmail(
                'confirmation',
                $session->getBeforeAuthUrl(),
                $store->getId()
            );
            $customerHelper = $this->_getHelper('customer');
            $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.',
                $customerHelper->getEmailConfirmationUrl($customer->getEmail())));
            $url = $this->_getUrl('*/*/index', array('_secure' => true));

        } else {
            $session->setCustomerAsLoggedIn($customer);
            $url = $this->_welcomeCustomer($customer);
        }
        //$this->_redirectSuccess($url);
        $this->_redirectUrl(Mage::getBaseUrl().'registered-sucess');
        return $this;
    }

         /**
     * Account Preferences On Account Dashboard
     */
    public function preferencesAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $block = $this->getLayout()->getBlock('customer_preferences');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $data = $this->_getSession()->getCustomerFormData(true);
        $customer = $this->_getSession()->getCustomer();
        if (!empty($data)) {
            $customer->addData($data);
        }
        if ($this->getRequest()->getParam('changepass') == 1) {
            $customer->setChangePassword(1);
        }

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Email Preferences'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }

    public function prefrencePostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/preferences');
        }

        if ($this->getRequest()->isPost()) {
           
            /*-------------Edited by Humera Batool (28/03/2017) for saving store view in newsletter---------------------------*/
            $country =$this->getRequest()->getParam('country');
            $language =$this->getRequest()->getParam('language');
            $is_subscribed = $this->getRequest()->getParam('is_subscribed');
            $dob = $this->getRequest()->getParam('dob');
            $store_code = Mage::getModel('customer/customer')->getNewsletterView($country,$language);
            /*-----*/

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getSession()->getCustomer();
            

            /** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = $this->_getModel('customer/form');
            $customerForm->setFormCode('customer_account_edit')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());
            
            $errors = array();
            $customerErrors = $customerForm->validateData($customerData);
            if ($customerErrors !== true) {
                $errors = array_merge($customerErrors, $errors);
            } else {
                $customerForm->compactData($customerData);
                $errors = array();

                // Validate account and compose list of errors if any
                $customerErrors = $customer->validate();
                if (is_array($customerErrors)) {
                    $errors = array_merge($errors, $customerErrors);
                }
            }
            if (!empty($errors)) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
                foreach ($errors as $message) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/preferences');
                return $this;
            }
            
            try {
                $customer->cleanPasswordsValidationData();
                $customer->save();
                $this->_getSession()->setCustomer($customer)
                    ->addSuccess($this->__('The account information has been saved.'));
                /*-------------Edited by Humera Batool (28/03/2017) for saving store view in newsletter---------------------------*/
                if($is_subscribed == 1):
                    $store_code = Mage::app()->getStore()->getStoreId();
                endif;
                if($this->getRequest()->getParam('dob')):
                Mage::getModel('customer/customer')->saveDob($dob,$customer->getId());
                endif;
                Mage::getModel('customer/customer')->saveNewsletterView($store_code,$customer->getEmail(),$is_subscribed,$customer->getId());
                /*----*/
                $this->_redirect('customer/account');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));
            }
        
        }
        
        $this->_redirect('*/*/preferences');
    }


    public function resetPasswordPostAction()
    {
        list($customerId, $resetPasswordLinkToken) = $this->_getRestorePasswordParameters($this->_getSession());
        $password = (string)$this->getRequest()->getPost('password');
        $passwordConfirmation = (string)$this->getRequest()->getPost('confirmation');

        try {
            $this->_validateResetPasswordLinkToken($customerId, $resetPasswordLinkToken);
        } catch (Exception $exception) {
            $this->_getSession()->addError($this->_getHelper('customer')->__('Your password reset link has expired.'));
            $this->_redirect('*/*/');
            return;
        }

        $errorMessages = array();
        if (iconv_strlen($password) <= 0) {
            array_push($errorMessages, $this->_getHelper('customer')->__('New password field cannot be empty.'));
        }
        /** @var $customer Mage_Customer_Model_Customer */
        $customer = $this->_getModel('customer/customer')->load($customerId);

        $customer->setPassword($password);
        $customer->setPasswordConfirmation($passwordConfirmation);
        $validationErrorMessages = $customer->validateResetPassword();
        if (is_array($validationErrorMessages)) {
            $errorMessages = array_merge($errorMessages, $validationErrorMessages);
        }

        if (!empty($errorMessages)) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
            foreach ($errorMessages as $errorMessage) {
                $this->_getSession()->addError($errorMessage);
            }
            $this->_redirect('*/*/changeforgotten');
            return;
        }

        try {
            // Empty current reset password token i.e. invalidate it
            $customer->setRpToken(null);
            $customer->setRpTokenCreatedAt(null);
            //$customer->cleanPasswordsValidationData();
            $customer->save();

            $this->_getSession()->unsetData(self::TOKEN_SESSION_NAME);
            $this->_getSession()->unsetData(self::CUSTOMER_ID_SESSION_NAME);

            $this->_getSession()->addSuccess($this->_getHelper('customer')->__('Your password has been updated.'));
            $this->_redirect('*/*/login');
        } catch (Exception $exception) {
            $this->_getSession()->addException($exception, $this->__('Cannot save a new password.'));
            $this->_redirect('*/*/changeforgotten');
            return;
        }
    }

    public function editPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/edit');
        }

        if ($this->getRequest()->isPost()) {
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getSession()->getCustomer();

            /** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = $this->_getModel('customer/form');
            $customerForm->setFormCode('customer_account_edit')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());

            $errors = array();
            $customerErrors = $customerForm->validateData($customerData);
            if ($customerErrors !== true) {
                $errors = array_merge($customerErrors, $errors);
            } else {
                $customerForm->compactData($customerData);
                $errors = array();

                // If password change was requested then add it to common validation scheme
                if ($this->getRequest()->getParam('change_password')) {
                    $currPass   = $this->getRequest()->getPost('current_password');
                    $newPass    = $this->getRequest()->getPost('password');
                    $confPass   = $this->getRequest()->getPost('confirmation');

                    $oldPass = $this->_getSession()->getCustomer()->getPasswordHash();
                    if ( $this->_getHelper('core/string')->strpos($oldPass, ':')) {
                        list($_salt, $salt) = explode(':', $oldPass);
                    } else {
                        $salt = false;
                    }

                    if ($customer->hashPassword($currPass, $salt) == $oldPass) {
                        if (strlen($newPass)) {
                            /**
                             * Set entered password and its confirmation - they
                             * will be validated later to match each other and be of right length
                             */
                            $customer->setPassword($newPass);
                            $customer->setPasswordConfirmation($confPass);
                        } else {
                            $errors[] = $this->__('New password field cannot be empty.');
                        }
                    } else {
                        $errors[] = $this->__('Invalid current password');
                    }
                }

                // Validate account and compose list of errors if any
                $customerErrors = $customer->validate();
                if (is_array($customerErrors)) {
                    $errors = array_merge($errors, $customerErrors);
                }
            }

            if (!empty($errors)) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
                foreach ($errors as $message) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/edit');
                return $this;
            }

            try {
                //$customer->cleanPasswordsValidationData();
                $customer->save();
                $this->_getSession()->setCustomer($customer)
                    ->addSuccess($this->__('The account information has been saved.'));
                if(Mage::getSingleton('customer/session')->getBeforeAuthUrl()){
                    Mage::app()->getResponse()->setRedirect(Mage::getSingleton('customer/session')->getBeforeAuthUrl());
                }
                else {
                    $this->_redirect('customer/account');
                }
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));
            }
        }

        $this->_redirect('*/*/edit');
    }

    public function createPostAction()
    {
        $errUrl = $this->_getUrl('*/*/login', array('_secure' => true));

        if (!$this->_validateFormKey()) {
            $this->_redirectError($errUrl);
            return;
        }

        /** @var $session Mage_Customer_Model_Session */
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }

        if (!$this->getRequest()->isPost()) {
            $this->_redirectError($errUrl);
            return;
        }

        $customer = $this->_getCustomer();

        try {
            $errors = $this->_getCustomerErrors($customer);

            if (empty($errors)) {
                //saving country in customer account added on 30 april 2018 by Humera Batool
                $countryCode = Mage::getStoreConfig('onestepcheckout/general/country');
                $customer->setCustomerCountry($countryCode);
                $customer->save();
                //code end
                $this->_dispatchRegisterSuccess($customer);
                /*If session is not created for firsttime registration */
                if( ! Mage::getSingleton('checkout/session')->getRegisterCustomerFirstTime()){
                    Mage::getSingleton('checkout/session')->setRegisterCustomerFirstTime("recent");
                }
                $this->_successProcessRegistration($customer);
                return;
            } else {
                $this->_addSessionError($errors);
            }
        } catch (Mage_Core_Exception $e) {
            $session->setCustomerFormData($this->getRequest()->getPost());
            if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                $url = $this->_getUrl('customer/account/forgotpassword');
                $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
            } else {
                $message = $this->_escapeHtml($e->getMessage());
            }
            $session->addError($message);
        } catch (Exception $e) {
            $session->setCustomerFormData($this->getRequest()->getPost());
            $session->addException($e, $this->__('Cannot save the customer.'));
        }

        $this->_redirectError($errUrl);
    }

    public function confirmAction()
    {
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_getSession()->logout()->regenerateSessionId();
        }
        try {
            $id      = $this->getRequest()->getParam('id', false);
            $key     = $this->getRequest()->getParam('key', false);
            $backUrl = $this->getRequest()->getParam('back_url', false);

            // load customer by id (try/catch in case if it throws exceptions)
            try {
                $customer = $this->_getModel('customer/customer')->load($id);
                if ((!$customer) || (!$customer->getId())) {
                    throw new Exception('Failed to load customer by id.');
                }
            }
            catch (Exception $e) {
                throw new Exception($this->__('Wrong customer account specified.'));
            }

            // check if it is inactive
            if ($customer->getConfirmation()) {
                if ($customer->getConfirmation() !== $key) {
                    throw new Exception($this->__('Wrong confirmation key.'));
                }

                // activate customer
                try {
                    $customer->setConfirmation(null);
                    $customer->save();
                }
                catch (Exception $e) {
                    throw new Exception($this->__('Failed to confirm customer account.'));
                }

                // log in and send greeting email, then die happy
                $session->setCustomerAsLoggedIn($customer);
                $successUrl = $this->_welcomeCustomer($customer, true);
                $this->_redirectSuccess($backUrl ? $backUrl : $successUrl);
                return;
            }

            // die happy
            $this->_redirectSuccess($this->_getUrl('*/*/index', array('_secure' => true)));
            return;
        }
        catch (Exception $e) {
            // die unhappy
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectError($this->_getUrl('*/*/index', array('_secure' => true)));
            return;
        }
    }

}

?>