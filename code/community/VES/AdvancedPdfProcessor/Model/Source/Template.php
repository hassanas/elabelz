<?php

class VES_AdvancedPdfProcessor_Model_Source_Template
{
	public function toOptionArray()
    {
        $collection = Mage::getModel('advancedpdfprocessor/template')->getCollection();
        $options 	= array();
        $options[] = array('value'=>'','label'=>'');
        foreach($collection as $option){
        	$options[] = array('value'=>$option->getId(), 'label'=>$option->getName());
        }
        return $options;
    }
}