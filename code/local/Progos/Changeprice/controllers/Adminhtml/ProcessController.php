<?php
class Progos_Changeprice_Adminhtml_ProcessController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed(){
		return true;
	}

    public function updateAction(){
		$model = Mage::getModel('changeprice/cron');
        echo $model->getUpdateProductCollection() ;
    }
}