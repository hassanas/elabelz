<?php
/*
 * Author: hassan.ali@progos.org
 *
 * */
class Progos_Rma_Model_Email_Template extends AW_Rma_Model_Email_Template
{
    /**
     * progos: Override to add $this->setTemplateSubject($vars['subject']);
     *
     * Sends email
     *
     * @param $sender
     * @param $email
     * @param $name
     * @param $vars
     * @param $storeId
     *
     * @return AW_Rma_Model_Email_Template
     */
    public function sendEmail($sender, $email, $name, $vars = array(), $storeId = null)
    {
        $this->setSentSuccess(false);

        if (!$email) {
            return $this;
        }

        if (($storeId === null) && $this->getDesignConfig()->getStore()) {
            $storeId = $this->getDesignConfig()->getStore();
        }

        if (!is_array($sender)) {
            $this->setSenderName(Mage::getStoreConfig('trans_email/ident_' . $sender . '/name', $storeId));
            $this->setSenderEmail(Mage::getStoreConfig('trans_email/ident_' . $sender . '/email', $storeId));
            $this->setTemplateSubject($vars['subject']);
        } else {
            $this->setSenderName($sender['name']);
            $this->setSenderEmail($sender['email']);
            $this->setTemplateSubject($vars['subject']);
        }

        if (!isset($vars['store'])) {
            $vars['store'] = Mage::app()->getStore($storeId);
        }

        if ($this->getProcessedTemplate($vars)) {
            $this->setSentSuccess($this->send($email, $name, $vars));
        } else {
            $this->setSentSuccess(true);
        }
        return $this;
    }
}
		