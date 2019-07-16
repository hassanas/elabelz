<?php

/**
 *
 * @category   Progos
 * @package    Progos_Core
 * @author     Hassan Ali Shahzad (hassan.ali@progos.org)
 * Date:       04-07-2017
 *
 * This Model extended to add functionality of Reply-from header into new orders
 */
class Progos_Core_Model_Email_Template_Mailer extends Mage_Core_Model_Email_Template_Mailer
{

    /**
     * Send all emails from email list
     * @see self::$_emailInfos
     *
     * @return Mage_Core_Model_Email_Template_Mailer
     * on line 30 function setReplyTo added
     */

    public function send()
    {
        /** @var $emailTemplate Mage_Core_Model_Email_Template */
        $emailTemplate = Mage::getModel('core/email_template');
        // Send all emails from corresponding list
        while (!empty($this->_emailInfos)) {
            $emailInfo = array_pop($this->_emailInfos);
            // Add Reply-to functionality
            if (!empty($this->getReplyTo()))
                $emailTemplate->setReplyTo($this->getReplyTo());
            // Handle "Bcc" recipients of the current email
            $emailTemplate->addBcc($emailInfo->getBccEmails());
            // Set required design parameters and delegate email sending to Mage_Core_Model_Email_Template
            $emailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $this->getStoreId()))
                ->setQueue($this->getQueue())
                ->sendTransactional(
                    $this->getTemplateId(),
                    $this->getSender(),
                    $emailInfo->getToEmails(),
                    $emailInfo->getToNames(),
                    $this->getTemplateParams(),
                    $this->getStoreId()
                );
        }
        return $this;
    }

    /**
     * @param $replyto
     * @return Varien_Object
     * From mailer class you can set this filed when you init class like in following class I added
     * app\code\local\Rewrite\Sales\Model\Order.php
     *
     */
    public function setReplyTo($replyto)
    {
        return $this->setData('replyto', $replyto);
    }

    /**
     * @return mixed
     */
    public function getReplyTo()
    {
        return $this->_getData('replyto');
    }
}
