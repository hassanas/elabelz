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
 * @package     Mage_Newsletter
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Newsletter subscribe controller
 *
 * @category    Mage
 * @package     Mage_Newsletter
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Newsletter/controllers/SubscriberController.php';

class Progos_Newsletter_SubscriberController extends Mage_Newsletter_SubscriberController
{
    /**
     * Values that we accept as customer gender.
     * @var array
     */
    protected $allowedSubscriberValues = array(1, 2);

    /**
     * New subscription action
     */
    public function newAction()
    {
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
            $session = Mage::getSingleton('core/session');
            $customerSession = Mage::getSingleton('customer/session');
            $email = (string)$this->getRequest()->getPost('email');
            try {
                if (!Zend_Validate::is($email, 'EmailAddress')) {
                    Mage::throwException($this->__('Please enter a valid email address.'));
                }

                if (Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1
                    && !$customerSession->isLoggedIn()
                ) {
                    Mage::throwException(
                        $this->__('Sorry, but administrator denied subscription for guests. Please <a href="%s">register</a>.',
                        Mage::helper('customer')->getRegisterUrl()));
                }

                $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                if ($this->getRequest()->getPost('subscriber_name')) {
                    $gender = (int)$this->getRequest()->getPost('subscriber_name');
                    if (!in_array($gender, $this->allowedSubscriberValues)) {
                        $gender = "";
                    }
                    $model = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                    $model->setSubscriberName($gender);
                    $model->save();
                }

                if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                    $session->addSuccess($this->__('Confirmation request has been sent.'));
                } else {
                    $session->addSuccess("<p style='padding-bottom:10px;'>".$this->__("We've just sent your discount code to your email.")."</p><p>".$this->__("P.S. Don't forget to check your spam folder and mark the email as 'Not Spam' ")."</p>");
                }

                $eventData = array('email'=>$email,'subscriber_name'=>$gender);
                Mage::dispatchEvent('progos_newsletter_subscriber_save_after', $eventData);
                $this->_redirectUrl(Mage::getBaseUrl() . 'subscribe-scusssfully');
            } catch (Mage_Core_Exception $e) {
                $session->addException($e, $this->__('There was a problem with the subscription: %s', $e->getMessage()));
                $this->_redirect('/');
            } catch (Exception $e) {
                $session->addException($e, $this->__('There was a problem with the subscription.'));
                $this->_redirect('/');
            }
        }
    }
}
