<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Simpleseo
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Checkout observer model
 *
 * @category   Mage
 * @package    Mage_SimpleSeo
 * @author      Magento Community Team (kvhsolutions@gmail.com)
 */
class Kvh_Simpleseo_Model_Observer 
{
		   public function addMetaTitle(Varien_Event_Observer $observer)
		{ 
			$model = Mage::registry('cms_page');
			$form = $observer->getEvent()->getForm(); 
				//get CMS model with data
        
        //get form instance
        //$form = $observer->getForm();
        //create new custom fieldset 'atwix_content_fieldset'
     	 //$fieldset = $form->addFieldset('kvh_content_fieldset', array('legend'=>Mage::helper('cms')->__('Meta Data'),'class'=>'fieldset-wide'));
        //add new field
    //    $fieldset = $form->addFieldset('meta_fieldset', array('legend' => Mage::helper('cms')->__('Meta Data'), 'class' => 'fieldset-wide'));
		// $form = $observer->getEvent()->getForm();
       // $fieldset = $form->getFieldset('meta_fieldset');
		//$fieldset = $form->getFieldset('meta_fieldset');
		$fieldset = $form->getElement('meta_fieldset');
		
		
		
		$fieldset->addField('meta_title', 'text', array(
            'name'      => 'meta_title',
            'label'     => Mage::helper('cms')->__('Meta Title'),
            'title'     => Mage::helper('cms')->__('Meta Title'),
            'disabled'  => false,
            //set field value
            'value'     => $model->getMetaTitle()
        ));  
			 
		}
		
		
		 
		
		 
		
}
