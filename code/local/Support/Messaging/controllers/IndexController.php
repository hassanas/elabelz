<?php
class Support_Messaging_IndexController extends Mage_Core_Controller_Front_Action {

	public function indexAction() {}

	protected function _getSession() {
		Mage::getSingleton('core/session', array('name'=>$this->_sessionNamespace));
		return Mage::getSingleton('customer/session');
	}

	protected function _construct() {
		if($this->_getSession()->isLoggedIn()){
	        $this->_redirect("*/history");
	        return;
	    } else {
			Mage::getSingleton('core/session')->addError($this->__( 'You must login to access this page'));
			$this->_redirect('customer/account/login');
			return;
	    }
	    return;
    }

}