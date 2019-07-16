<?php
class Rewrite_PdfPro_Model_Sales_Order extends VES_PdfPro_Model_Sales_Order
{
  /**
     * Send email with order data
     *
     * @return Mage_Sales_Model_Order
     */
    public function sendNewOrderEmail()
    {
      if(!Mage::getStoreConfig('pdfpro/config/enabled')) return Rewrite_Sales_Model_Order::queueNewOrderEmail(true);
      switch(Mage::getStoreConfig('pdfpro/config/order_email_attach')){
        case VES_PdfPro_Model_Source_Attach::ATTACH_TYPE_NO:
          return Rewrite_Sales_Model_Order::queueNewOrderEmail(true);
        case VES_PdfPro_Model_Source_Attach::ATTACH_TYPE_ADMIN:
          $this->sendNewOrderEmailForAdmin(true);
          $this->sendNewOrderEmailForCustomer(false);
          return $this;
        case VES_PdfPro_Model_Source_Attach::ATTACH_TYPE_CUSTOMER:
          $this->sendNewOrderEmailForAdmin(false);
          $this->sendNewOrderEmailForCustomer(true);
          return $this;
      }
        $storeId = $this->getStore()->getId();

        if (!Mage::helper('sales')->canSendNewOrderEmail($storeId)) {
            return $this;
        }
        $customerCountry = $this->getBillingAddress()->getCountryId();
        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);

        // Start store emulation process
        $appEmulation = Mage::getSingleton('pdfpro/app_emulation');
        if($appEmulation) $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())
                ->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {
            // Stop store emulation process
            if($appEmulation) $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            throw $exception;
        }

        // Stop store emulation process
        if($appEmulation) $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        // Retrieve corresponding email template id and customer name
        if ($this->getCustomerIsGuest()) {
            if($customerCountry == "IQ"):
              $templateId = 31;
              $customerName = $this->getCustomerName();
              $mailer = Mage::getModel('pdfpro/email_template_mailer');
              $emailInfo = Mage::getModel('pdfpro/email_info');
              $emailInfo->addTo($this->getCustomerEmail(), $customerName);

              $mailer->addEmailInfo($emailInfo);

              $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
              $mailer->setStoreId($storeId);
              $mailer->setTemplateId($templateId);
              $mailer->setTemplateParams(array(
                'order'        => $this,
                'billing'      => $this->getBillingAddress(),
                'payment_html' => $paymentBlockHtml
              )
            );
            $mailer->send();
            endif;   
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
            $customerName = $this->getBillingAddress()->getName();
        
        } else {
            if($customerCountry == "IQ"):
              $templateId = 31;
              $customerName = $this->getCustomerName();
              $mailer = Mage::getModel('pdfpro/email_template_mailer');
              $emailInfo = Mage::getModel('pdfpro/email_info');
              $emailInfo->addTo($this->getCustomerEmail(), $customerName);

              $mailer->addEmailInfo($emailInfo);

              $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
              $mailer->setStoreId($storeId);
              $mailer->setTemplateId($templateId);
              $mailer->setTemplateParams(array(
                'order'        => $this,
                'billing'      => $this->getBillingAddress(),
                'payment_html' => $paymentBlockHtml
              )
            );
            $mailer->send();
            endif;
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
            $customerName = $this->getCustomerName();
        }
        $mailer = Mage::getModel('pdfpro/email_template_mailer');
        $emailInfo = Mage::getModel('pdfpro/email_info');
        $emailInfo->addTo($this->getCustomerEmail(), $customerName);
        if ($copyTo && $copyMethod == 'bcc') {
            // Add bcc to customer email
            foreach ($copyTo as $email) {
                $emailInfo->addBcc($email);
            }
        }
        $mailer->addEmailInfo($emailInfo);

        // Email copies are sent as separated emails if their copy method is 'copy'
        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $emailInfo = Mage::getModel('pdfpro/email_info');
                $emailInfo->addTo($email);
                $mailer->addEmailInfo($emailInfo);
            }
        }

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'order'        => $this,
                'billing'      => $this->getBillingAddress(),
                'payment_html' => $paymentBlockHtml
            )
        );
        /* Attach order PDF in email */
        $orderData= Mage::getModel('pdfpro/order')->initOrderData($this);
      try{
      $result = Mage::helper('pdfpro')->initPdf(array($orderData),'order');
      if($result['success']){
        $mailer->setPdf(array('filename'=>Mage::helper('pdfpro')->getFileName('order',$this).'.pdf', 'content'=>$result['content']));
      }else{
        Mage::log($result['msg']);
      }
    }catch(Exception $e){
      Mage::log($e->getMessage());
    }
    
        $mailer->send();

        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');

        return $this;
    }
    
    public function sendNewOrderEmailForCustomer($attachPdfFile = true){
      $storeId = $this->getStore()->getId();

        if (!Mage::helper('sales')->canSendNewOrderEmail($storeId)) {
            return $this;
        }
        
        $customerCountry = $this->getBillingAddress()->getCountryId();
        // Start store emulation process
        $appEmulation = Mage::getSingleton('pdfpro/app_emulation');
        if($appEmulation) $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())
                ->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {
            // Stop store emulation process
            if($appEmulation) $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            throw $exception;
        }

        // Stop store emulation process
        if($appEmulation) $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        // Retrieve corresponding email template id and customer name
        if ($this->getCustomerIsGuest()) {
            if($customerCountry == "IQ"):
              $templateId = 31;
              $customerName = $this->getCustomerName();
              $mailer = Mage::getModel('pdfpro/email_template_mailer');
              $emailInfo = Mage::getModel('pdfpro/email_info');
              $emailInfo->addTo($this->getCustomerEmail(), $customerName);

              $mailer->addEmailInfo($emailInfo);

              $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
              $mailer->setStoreId($storeId);
              $mailer->setTemplateId($templateId);
              $mailer->setTemplateParams(array(
                'order'        => $this,
                'billing'      => $this->getBillingAddress(),
                'payment_html' => $paymentBlockHtml
              )
            );
            $mailer->send();
            endif;  
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
            
            $customerName = $this->getBillingAddress()->getName();
        } else {
            if($customerCountry == "IQ"):
              $templateId = 31;
              $customerName = $this->getCustomerName();
              $mailer = Mage::getModel('pdfpro/email_template_mailer');
              $emailInfo = Mage::getModel('pdfpro/email_info');
              $emailInfo->addTo($this->getCustomerEmail(), $customerName);

              $mailer->addEmailInfo($emailInfo);
              $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
              $mailer->setStoreId($storeId);
              $mailer->setTemplateId($templateId);
              $mailer->setTemplateParams(array(
                'order'        => $this,
                'billing'      => $this->getBillingAddress(),
                'payment_html' => $paymentBlockHtml
               )
            );
            $mailer->send();
            endif;   
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
             
            $customerName = $this->getCustomerName();
        }

        $mailer = Mage::getModel('pdfpro/email_template_mailer');
        $emailInfo = Mage::getModel('pdfpro/email_info');
        $emailInfo->addTo($this->getCustomerEmail(), $customerName);

        $mailer->addEmailInfo($emailInfo);

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'order'        => $this,
                'billing'      => $this->getBillingAddress(),
                'payment_html' => $paymentBlockHtml
            )
        );
        if($attachPdfFile){
          /* Attach order PDF in email */
          $orderData= Mage::getModel('pdfpro/order')->initOrderData($this);
        try{
        $result = Mage::helper('pdfpro')->initPdf(array($orderData),'order');
        if($result['success']){
          $mailer->setPdf(array('filename'=>Mage::helper('pdfpro')->getFileName('order',$this).'.pdf', 'content'=>$result['content']));
        }else{
          Mage::log($result['msg']);
        }
      }catch(Exception $e){
        Mage::log($e->getMessage());
      }
        }
    
        $mailer->send();

        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');

        return $this;
    }

    /*
     *
     * Hassan:  This function override to add registry comment-added to yes on which we place check if registry set then just sync order comment instead to whole order with mageworks orders grid
    * Add a comment to order
    * Different or default status may be specified
    *
    * @param string $comment
    * @param string $status
    * @return Mage_Sales_Model_Order_Status_History
    */
    public function addStatusHistoryComment($comment, $status = false)
    {
        if (false === $status) {
            $status = $this->getStatus();
        } elseif (true === $status) {
            $status = $this->getConfig()->getStateDefaultStatus($this->getState());
        } else {
            $this->setStatus($status);
        }
        $history = Mage::getModel('sales/order_status_history')
            ->setStatus($status)
            ->setComment($comment)
            ->setEntityName($this->_historyEntityName);
        $this->addStatusHistory($history);
        if (!Mage::registry('comment-added')) {
            Mage::register('comment-added', 'yes');
        }
        return $history;
    }
}