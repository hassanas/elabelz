<?php
class Progos_Updateurlkey_Adminhtml_ProcessController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed(){
		return true;
	}

    public function updateAction(){
        $model = Mage::getModel('updateurlkey/updateurlkey');
        echo $model->run() ;
    }
}