<?php

class Progos_Sizeguide_Block_Adminhtml_Sizeguide_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array(
                'id' => $this->getRequest()->getParam('id'),
            )),
            'method' => 'post',
            'enctype' => 'multipart/form-data',
       ));
      
      $this->setForm($form);

      $fieldset = $form->addFieldset('sizeguide_form', array('legend'=>Mage::helper('sizeguide')->__('Size Guide information')));


	  	$helper = Mage::helper('sizeguide/sizeguide');
	  	$id     = $this->getRequest()->getParam('id');
	  	$model  = Mage::getModel('sizeguide/sizeguide')->load($id);
    	$categories = $helper->getCategories();
    	$brandCollection = $helper->getBrandCollection();
    	

      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('sizeguide')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
          'value'			=> $model->getTitle(),
      ));

      $fieldset->addField('name', 'text', array(
          'label'     => Mage::helper('sizeguide')->__('Name'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'name',
          'value'			=> $model->getName(),
      ));

      
      $fieldset->addField('categories', 'multiselect', array(
          'label'     => Mage::helper('sizeguide')->__('Categories'),
          'class'     => 'required-entry categoryids',
          'required'  => true,
          'name'      => 'categories[]',
          'values'    => $categories,
          'value'     => ( ( !empty( $model->getCategories()) ) ? explode('|', $model->getCategories()) : array() )
      ));
    
      $fieldset->addField('brand_ids', 'multiselect', array(
          'label'     => Mage::helper('sizeguide')->__('Brands'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'brand_ids[]',
          'values'		=>$brandCollection,
          'value'			=> ( ( !empty( $model->getBrandIds()) ) ? explode(',', $model->getBrandIds()) : array() )
      )); 
      $store = '';
      if( $model->getStoreId() != "" && $model->getStoreId() == "0" )
        $store = 0;
      else    
        $store = ( ( !empty( $model->getStoreId()) ) ? explode(',', $model->getStoreId()) : array() );

     	if (!Mage::app()->isSingleStoreMode()) {
          $fieldset->addField('store_id', 'multiselect', array(
              'name' => 'store_id[]',
              'label' => Mage::helper('sizeguide')->__('Store View'),
              'title' => Mage::helper('sizeguide')->__('Store View'),
              'required' => true,
              'value' => $store,
              'values' => Mage::getSingleton('adminhtml/system_store')
                           ->getStoreValuesForForm(false, true),
          ));
      }else {
          $fieldset->addField('store_id', 'hidden', array(
              'name' => 'store_id[]',
              'value' => Mage::app()->getStore(true)->getId()
          ));
      }
      $samples = Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB) . 'sizeguide/tableSamples/';
      if( !empty($model->getSizeguideFile()) ){
        $url = Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB) . 'sizeguide/'.$model->getSizeguideFile();
        $fieldset->addField('sizeguide_file', 'file', array(
          'label'     => Mage::helper('sizeguide')->__('Size Table File'),
          'class'     => '',
          'name'      => 'sizeguide_file',
          'value'     => $url,
          'after_element_html' => 
          '<br><a target="__blank" href="'.$url.'"> '.$model->getSizeguideFile().' </a><br>
          <br><small>Supported file types: csv </small><input type="hidden" name="sp_hidden" value="'.$model->getSizeguideFile().'"><h1>Samples:</h1>'.'<a target="__blank" href="'.$samples.'template1.csv"> Template1 </a>'.
          '<br><a target="__blank" href="'.$samples.'template2.csv"> Template2 </a>'.
          '<br><a target="__blank" href="'.$samples.'template3.csv"> Template3 </a>',    
       ));
      }else{
        $fieldset->addField('sizeguide_file', 'file', array(
          'label'     => Mage::helper('sizeguide')->__('File'),
          'class'     => 'disable',
          'required'  => true,
          'class'     => 'required-entry',
          'name'      => 'sizeguide_file',
          'after_element_html' => 
          '<br><small>Supported file types: csv </small><input type="hidden" name="sp_hidden" value=""><h1>Samples:</h1>'.'<a target="__blank" href="'.$samples.'template1.csv"> Template1 </a>'.
          '<br><a target="__blank" href="'.$samples.'template2.csv"> Template2 </a>'.
          '<br><a target="__blank" href="'.$samples.'template3.csv"> Template3 </a>', 
         ));
      }

	  	$statusArray = array( 
	  	 									2=>'Disable',
	  	 									1=>'Enable'
	  	 								);
	    $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('sizeguide')->__('Status'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'status',
          'value'			=> $model->getStatus(),
          'values'		=> $statusArray 
      )); 
	  
      $this->setForm($form);
      return parent::_prepareForm();
  }
}