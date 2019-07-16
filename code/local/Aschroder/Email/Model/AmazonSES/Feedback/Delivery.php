<?php
/**
 * 
 *
 * @author Ashley Schroder (aschroder.com)
 * @copyright  Copyright (c) 2013 ASchroder Consulting Ltd
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Aschroder_Email_Model_AmazonSES_Feedback_Delivery extends Aschroder_Email_Model_AmazonSES_Feedback {
    
    /**
     * 
     * @param Object $obj
     */
    public function loadFromJSON($obj) {
        $this->setTimestamp($obj->timestamp);
        $this->setSmtpResponse($obj->smtpResponse);
        $this->setFeedbackId($obj->reportingMTA);

        $this->_recipients = array();
        foreach ($obj->recipients as $recipient) {
            $this->_recipients[] = new Aschroder_Email_Model_AmazonSES_Feedback_Delivery_Recipient($recipient);
            
        }
    }
    
    public function getFeedbackType() {
        return $this->getSmtpResponse();
    }
}
