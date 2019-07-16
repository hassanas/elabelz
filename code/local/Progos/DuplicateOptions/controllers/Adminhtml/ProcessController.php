<?php
class Progos_DuplicateOptions_Adminhtml_ProcessController extends Mage_Adminhtml_Controller_Action
{

	protected function _isAllowed()
	{
		//return Mage::getSingleton('admin/session')->isAllowed('duplicateoptions/duplicateoptionsbackend');
		return true;
	}

    public function removeAction()
    {	    	
		$rm = Mage::getModel('duplicateoptions/duplicate');

		/* get colors from csv and replace them in database */
		$fileExists = Mage::helper('duplicateoptions')->getOptionReplaceFile();

		if($fileExists) {
			$rm->getCsvToReplaceColors();
		}
		
		$rm->startProcessing();
    }
}