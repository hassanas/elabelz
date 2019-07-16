<?php
class Progos_Skuvault_Adminhtml_ProcessController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed(){
		return true;
	}

    public function addAction(){
	    $model = Mage::getModel('progos_skuvault/codecron');
        echo $model->addProductCode() ;
    }

    public function updateQuantityAction(){
        $model = Mage::getModel('progos_skuvault/productQtySync_cron');
        echo $model->syncQtyApiBased() ;
    }

    public function updateQuantitySkuBasedAction(){
        $model = Mage::getModel('progos_skuvault/productQtySync_cron');
        echo $model->syncQtySkuBased() ;
    }
}