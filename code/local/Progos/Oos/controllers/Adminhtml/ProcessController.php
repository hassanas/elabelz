<?php
class Progos_Oos_Adminhtml_ProcessController extends Mage_Adminhtml_Controller_Action
{
	protected function _isAllowed(){
		return true;
	}

    public function updateAction(){
        $model = Mage::getModel('oos/cron');
        echo $model->run() ;
    }

    public function updateoosAction(){
        $model = Mage::getModel('oos/updateoos');
        echo $model->run() ;
    }

    public function removeconfigAction(){
        $model = Mage::getModel('oos/cronconfig');
        echo $model->run() ;
    }
}