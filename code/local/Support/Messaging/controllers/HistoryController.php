<?php
class Support_Messaging_HistoryController extends Mage_Core_Controller_Front_Action {
	
	protected function _getSession() {
		Mage::getSingleton('core/session', array('name'=>$this->_sessionNamespace));
		return Mage::getSingleton('customer/session');
	}
	
	protected function _construct() {
		if(!$this->_getSession()->isLoggedIn()){
			Mage::getSingleton('core/session')->addError($this->__( 'You must login to access this page'));
			$this->_redirect('customer/account/login');
			return;
	    }
	}
    
    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function showAction() {

    	$thread_id = $this->getRequest()->getParam("id");
    	$file_id = $this->getRequest()->getParam("file");
    	if ($file_id && $thread_id) {
    		$attachment = Mage::getModel("messaging/attachments")->load($file_id);
    		$get_message = Mage::getModel("messaging/conversation")->load($attachment->getMessageId(), "message_id");
    		$get_thread = Mage::getModel("messaging/thread")->load($get_message->getThreadId());

			
			$is_seller = Mage::helper("messaging")->isSeller();
			$current_user = Mage::helper("messaging")->getSession();

	        $for = $get_thread->getFor();
	        $from = $get_thread->getFrom();
	        $download = true;
			if ($is_seller && ($current_user->getCustomerId() != $for)) {
				$download = false;
			} else {
				if (!$is_seller && ($current_user->getCustomerId() != $from)) {
					$download = false;
				}
			}

			if ($download) {
				$filepath = Mage::getBaseDir('media') . DS . 'messages' . DS . $attachment->getFileName();
		        $this->_download($filepath);
			} else {
				echo "Invalid file or file not found";
			}
    	} elseif(!$file_id && $thread_id) {
	        $this->loadLayout();
	        $this->renderLayout();
    	}
    }

	public function _download($filepath) {
        // $entityid = $this->getRequest()->getParam('entity_id');
        // $customer_data = Mage::getModel('customer/customer')->load($entityid);
        // $filename = '';
        // if($customer_data){
        //     $filename = $customer_data->getFileuploadname();
        // }

        if (! is_file ( $filepath ) || ! is_readable ( $filepath )) {
            throw new Exception ( );
        }
        $this->getResponse ()
                    ->setHttpResponseCode ( 200 )
                    ->setHeader ( 'Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true )
                     ->setHeader ( 'Pragma', 'public', true )
                    ->setHeader ( 'Content-type', 'application/force-download' )
                    ->setHeader ( 'Content-Length', filesize($filepath) )
                    ->setHeader ('Content-Disposition', 'attachment' . '; filename=' . basename($filepath) );
        $this->getResponse ()->clearBody ();
        $this->getResponse ()->sendHeaders ();
        readfile ( $filepath );
        exit;
    }
    

}