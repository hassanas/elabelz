<?php
require_once Mage::getModuleDir('controllers', 'Mage_Contacts') . DS . 'IndexController.php';
class Contact_Form_IndexController extends Mage_Contacts_IndexController
{
    const XML_PATH_SENDER_NOTIFICATION_ENABLED        = 'contacts/sender_notification/enabled';
    const XML_PATH_SENDER_NOTIFICATION_EMAIL_TEMPLATE = 'contacts/sender_notification/email_template';

    public function postAction()
    {
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);
 
                $error = false;
 
                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }
 
                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }
 
                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }
 
                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }
 
                /**************************************************************/
                $fileName = '';
                if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {
                    try {
                        $fileName       = $_FILES['attachment']['name'];
                        $fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
                        $fileNamewoe    = rtrim($fileName, $fileExt);
                        $fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;
 
                        $uploader       = new Varien_File_Uploader('attachment');
                        $uploader->setAllowedExtensions(array('doc', 'docx','pdf', 'jpg', 'png')); //add more file types you want to allow
                        $uploader->setAllowRenameFiles(false);
                        $uploader->setFilesDispersion(false);
                        $path = Mage::getBaseDir('media') . DS . 'contacts';
                        if(!is_dir($path)){
                            mkdir($path, 0777, true);
                        }
                        $uploader->save($path . DS, $fileName );
 
                    } catch (Exception $e) {
                                Mage::getSingleton('customer/session')->addError($e->getMessage());
                        $error = true;
                    }
                }
                /**************************************************************/
 
                if ($error) {
                    throw new Exception();
                }
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
 
                /**************************************************************/
                //sending file as attachment
                 if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {
                  
                $attachmentFilePath = Mage::getBaseDir('media'). DS . 'contacts' . DS . $fileName;
                  if(file_exists($attachmentFilePath)){
                    $fileContents = file_get_contents($attachmentFilePath);
                    $attachment   = $mailTemplate->getMail()->createAttachment($fileContents);
                    $attachment->filename = $fileName;
                  }
                /**************************************************************/
            }
 
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );
 
                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                 /* send sender notification */
                if (Mage::getStoreConfigFlag(self::XML_PATH_SENDER_NOTIFICATION_ENABLED)) {

                    $customerMailTemplate = Mage::getModel('core/email_template');
                  
                    if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {

                    $attachmentFilePath = Mage::getBaseDir('media'). DS . 'contacts' . DS . $fileName;
                    if(file_exists($attachmentFilePath)){
                        $fileContents = file_get_contents($attachmentFilePath);
                        $attachment   = $customerMailTemplate->getMail()->createAttachment($fileContents);
                        $attachment->filename = $fileName;
                    }
                }

                    /* @var $mailTemplate Mage_Core_Model_Email_Template */
                    $customerMailTemplate->setDesignConfig(array('area' => 'frontend'))
                        ->setReplyTo(self::XML_PATH_EMAIL_RECIPIENT)
                        ->sendTransactional(
                            Mage::getStoreConfig(self::XML_PATH_SENDER_NOTIFICATION_EMAIL_TEMPLATE),
                            Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                            $post['email'],
                            null,
                            array('data' => $postObject)
                        );

                    if (!$customerMailTemplate->getSentSuccess()) {
                        throw new Exception();
                    }
                }
 
                $translate->setTranslateInline(true);
 
                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                $this->_redirect('contact-us');
 
                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);
 
                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Cant submit'));
                $this->_redirect('contact-us');
                return;
            }
 
        } else {
            $this->_redirect('contact-us');
        }
    }

    /*
     * @author  : Saroop
     * @date    : 20-08-2017
     * @description : The Mobile App have different CMS page. So for redirection on its proper url for mobile
     * @changes : Action Name because for app we redirect it to on there same page.
     *          Line : 275 , 282 , 287
     * */
    public function postAppAction()
    {
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;

                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }

                /**************************************************************/
                $fileName = '';
                if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {
                    try {
                        $fileName       = $_FILES['attachment']['name'];
                        $fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
                        $fileNamewoe    = rtrim($fileName, $fileExt);
                        $fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;

                        $uploader       = new Varien_File_Uploader('attachment');
                        $uploader->setAllowedExtensions(array('doc', 'docx','pdf', 'jpg', 'png')); //add more file types you want to allow
                        $uploader->setAllowRenameFiles(false);
                        $uploader->setFilesDispersion(false);
                        $path = Mage::getBaseDir('media') . DS . 'contacts';
                        if(!is_dir($path)){
                            mkdir($path, 0777, true);
                        }
                        $uploader->save($path . DS, $fileName );

                    } catch (Exception $e) {
                        Mage::getSingleton('customer/session')->addError($e->getMessage());
                        $error = true;
                    }
                }
                /**************************************************************/

                if ($error) {
                    throw new Exception();
                }
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */

                /**************************************************************/
                //sending file as attachment
                if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {

                    $attachmentFilePath = Mage::getBaseDir('media'). DS . 'contacts' . DS . $fileName;
                    if(file_exists($attachmentFilePath)){
                        $fileContents = file_get_contents($attachmentFilePath);
                        $attachment   = $mailTemplate->getMail()->createAttachment($fileContents);
                        $attachment->filename = $fileName;
                    }
                    /**************************************************************/
                }

                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                /* send sender notification */
                if (Mage::getStoreConfigFlag(self::XML_PATH_SENDER_NOTIFICATION_ENABLED)) {

                    $customerMailTemplate = Mage::getModel('core/email_template');

                    if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {

                        $attachmentFilePath = Mage::getBaseDir('media'). DS . 'contacts' . DS . $fileName;
                        if(file_exists($attachmentFilePath)){
                            $fileContents = file_get_contents($attachmentFilePath);
                            $attachment   = $customerMailTemplate->getMail()->createAttachment($fileContents);
                            $attachment->filename = $fileName;
                        }
                    }

                    /* @var $mailTemplate Mage_Core_Model_Email_Template */
                    $customerMailTemplate->setDesignConfig(array('area' => 'frontend'))
                        ->setReplyTo(self::XML_PATH_EMAIL_RECIPIENT)
                        ->sendTransactional(
                            Mage::getStoreConfig(self::XML_PATH_SENDER_NOTIFICATION_EMAIL_TEMPLATE),
                            Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                            $post['email'],
                            null,
                            array('data' => $postObject)
                        );

                    if (!$customerMailTemplate->getSentSuccess()) {
                        throw new Exception();
                    }
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                $this->_redirect('m-contact-us');

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Cant submit'));
                $this->_redirect('m-contact-us');
                return;
            }

        } else {
            $this->_redirect('m-contact-us');
        }
    }
}