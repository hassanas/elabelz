<?php

/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     0.1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */

/**
 * Get seller commission
 * Form to get the seller commission from admin
 */
class Apptha_Marketplace_Block_Adminhtml_Sellerreview_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {
 
 /**
  * Get the get the seller commission from admin
  *
  * @return void
  */
 
 
 protected function _prepareForm() {
 	/**
 	 * Get Seller Id
 	 */
  $seller_id = $this->getRequest ()->getParam ( 'id' );
  /**
   * Load Seller Id 
   * */
  $collection = Mage::getModel ( 'marketplace/sellerprofile' )->load ( $seller_id, 'seller_id' );
  $sellerHelper  = Mage::helper('marketplace/marketplace');
  $categories = $sellerHelper->getCategories();
  $categoriesArray = array();
  foreach($categories as $key => $category):
		$categoriesArray[$key]['value'] = $category->getId() ; 
		$categoriesArray[$key]['label'] = $category->getName() ; 
  endforeach;

  // echo  '<pre>';
  // print_r($collection);
  // echo  '</pre>';
  /**
   * Set Collection
   * */
  $this->setCollection ( $collection );
  /** New Varien Form */
      
      $form  = new Varien_Data_Form(array(
                'id'      => 'edit_form',
                'action'  => $this->getUrl('*/adminhtml_manageseller/saveprofile', 
                            array('id' => $seller_id)
                  ),
                'method'  => 'post',
                'enctype' => 'multipart/form-data'
              )
            );
      
      $fieldset = $form->addFieldset('store_name', array('legend' => Mage::helper('marketplace')->__('Store title')));
      
      $fieldset->addField('store_title', 'text', array(
            'name'      => 'store_title',
            'title'     => Mage::helper('marketplace')->__('Store title'),
            'label'     => Mage::helper('marketplace')->__('Store title'),
            'required'  => true,
            'value'     => $collection['store_title']
        )); 
      
      $fieldset->addField('state_name', 'text', array(
            'name'      => 'state_name',
            'title'     => Mage::helper('marketplace')->__('State'),
            'label'     => Mage::helper('marketplace')->__('State'),
            'required'  => true,
            'value'     => $collection['state']
        ));  
      
      /*$fieldset->addField('country', 'select', array(
          'name'      => 'country',
          'title'     => Mage::helper('marketplace')->__('country'),
          'label'     => Mage::helper('marketplace')->__('country'),
          'required'  => true,
          'value'     => $collection['country']
      )); */
 
      if(isset($collection['store_banner']) && $collection['store_banner'])
      {
        $fieldset->addField('store_banner', 'image', array(
            'label'     => Mage::helper('marketplace')->__('Store Banner'),
            'required'  => false,
            'name'      => 'store_banner',
            'value'     => Mage::getBaseUrl('media') . 'sellerimage/'.$collection['store_banner'],            
            'after_element_html' => '<br><small>Supported file types: .jpeg, .jpg, .gif, .png</small><input type="hidden" name="sb_hidden" value="'.$collection['store_banner'].'">',  
        ));
      }else{ 

      $fieldset->addField('store_banner', 'image', array(
          'label'     =>  Mage::helper('marketplace')->__('Store Banner'),
          'required'  =>  false,
          'name'      =>  'store_banner',
          'after_element_html' => '<br><small>Supported file types: .jpeg, .jpg, .gif, .png</small>',            
      ));
        }
      $fieldset->addField('country', 'select', array(
            'name'  => 'country',
            'label'     => Mage::helper('marketplace')->__('Country'),
            'values'    => Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(),
            'class' => 'required-entry',
            'required' => true,
            'selected' => 'selected',
            'value' => $collection['country']
      ));

      $fieldset->addField('contact', 'text', array(
        'name'      => 'contact',
        'label'     => Mage::helper('marketplace')->__('Contact'),
        'title'     => Mage::helper('marketplace')->__('Contact'),
        'required'  => true,
        'value'     => $collection['contact']
      ));  
      
	  if( $collection['sample_product_profile_file'] != '' ){
		  $fieldset->addField('sample_product_profile_file', 'image', array(
				'label'     => Mage::helper('marketplace')->__('Upload your profile or sample products'),
				'class'     => 'disable',
				'required'  => true,
				'name'      => 'sample_product_profile_file',
				'value'     => Mage::getBaseUrl('media') . 'sellerimage/'.$collection['sample_product_profile_file'],
				'after_element_html' => '<br><a target="__blank" href="'.Mage::getBaseUrl('media') . 'sellerimage/'.$collection['sample_product_profile_file'].'"> '.$collection['sample_product_profile_file'].' </a><br>
				<br><small>Supported file types: pdf , zip </small><input type="hidden" name="sp_hidden" value="'.$collection['sample_product_profile_file'].'">',		
		   ));
	  }else{
			$fieldset->addField('sample_product_profile_file', 'image', array(
				'label'     => Mage::helper('marketplace')->__('Upload your profile or sample products'),
				'class'     => 'disable',
				'required'  => true,
				'name'      => 'sample_product_profile_file',
		   ));
	  }
	  
	  $fieldset->addField('category_product', 'select', array(
            'name'  => 'category_product',
            'label'     => Mage::helper('marketplace')->__('What Product are you selling?'),
            'values'    => $categoriesArray ,
            'class' => 'required-entry',
            'required' => true,
            'selected' => 'selected',
            'value' => $collection['category_product']
      ));
	  
	  $fieldset->addField('have_store', 'radios', array(
          'label'     => Mage::helper('marketplace')->__('Do You have a Store'),
          'name'      => 'have_store',
          'onclick' => "",
          'onchange' => "",
		  'value'	=>	$collection['have_store'],
          'values' => array(
                            array('value'=>'yes','label'=>'Yes'),
                            array('value'=>'no','label'=>'No'),
                       ),
          'disabled' => false,
          'readonly' => false,
       ));
	  
	  
	  $fieldset->addField('no_of_style', 'text', array(
        'name'      => 'no_of_style',
        'label'     => Mage::helper('marketplace')->__('How Many Styles Do You Have?'),
        'title'     => Mage::helper('marketplace')->__('How Many Styles Do You Have?'),
        'required'  => true,
        'value'     => $collection['no_of_style']
      )); 
	  
      $fieldset->addField('description', 'textarea', array(
        'name'      => 'description',
        'label'     => Mage::helper('marketplace')->__('About your shop'),
        'title'     => Mage::helper('marketplace')->__('About your shop'),
        'required'  => true,
        'value'     => $collection['description']
      ));  
      
      $fieldset->addField('supplier_address', 'textarea', array(
        'name'      => 'supplier_address',
        'label'     => Mage::helper('marketplace')->__('Supplier Address'),
        'title'     => Mage::helper('marketplace')->__('Supplier Address'),
        'required'  => true,
        'value'     => $collection['supplier_address']
      ));  
      
      $fieldset->addField('bank_payment', 'textarea', array(
        'label'     => Mage::helper('marketplace')->__('Bank Account Detail'),
        'title'     => Mage::helper('marketplace')->__('Bank Account Detail'),
        'name'      => 'bank_payment',
        'required'  => true,
        'value'     => $collection['bank_payment']
      ));  

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
 }

}

