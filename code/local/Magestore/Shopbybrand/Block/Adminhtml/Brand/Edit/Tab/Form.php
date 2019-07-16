<?php
/**
 * Magestore
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magestore
 * @package     Magestore_Shopbybrand
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Shopbybrand Edit Form Content Tab Block
 * 
 * @category    Magestore
 * @package     Magestore_Shopbybrand
 * @author      Magestore Developer
 */
class Magestore_Shopbybrand_Block_Adminhtml_Brand_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * prepare tab form's information
     *
     * @return Magestore_Shopbybrand_Block_Adminhtml_Shopbybrand_Edit_Tab_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        
        $dataObj = new Varien_Object(array(
            'store_id' => '',
            'name_in_store' => '',
            'url_key_in_store'   =>  '',
            'meta_keywords_in_store' =>  '',
            'meta_description_in_store' =>  '',
            'short_description_in_store' =>  '',
            'description_in_store' =>  '',
            'is_featured_in_store' =>  '',
            'image_in_store' =>  '',
            'is_designer_image_in_store' =>  '',
            'thumbnail_image_in_store' =>  '',
            'status_in_store_in_store' => '',
            'is_designer_in_store' =>  '',
            'is_upcoming_in_store' => '',
            'precentage_discount_in_store' => '',
            'information_description_in_store' => '',
            'information_title_in_store' => '',
            'is_show_information_tab_in_store' => ''
        ));
        
        if (Mage::getSingleton('adminhtml/session')->getBrandData()) {
            $data = Mage::getSingleton('adminhtml/session')->getBrandData();
            Mage::getSingleton('adminhtml/session')->setBrandData(null);
        } elseif (Mage::registry('brand_data')) {
            $data = Mage::registry('brand_data')->getData();
        }
        
        if (isset($data)) $dataObj->addData($data);
            $data = $dataObj->getData();
        
        $storeId = $this->getRequest()->getParam('store');
        if($storeId)
            $store = Mage::getModel('core/store')->load($storeId);
        else
            $store = Mage::app()->getStore();
        $inStore = $this->getRequest()->getParam('store');
        $defaultLabel = Mage::helper('shopbybrand')->__('Use Default');
        $defaultTitle = Mage::helper('shopbybrand')->__('-- Please Select --');
        $scopeLabel = Mage::helper('shopbybrand')->__('STORE VIEW');
        
        $fieldset = $form->addFieldset('shopbybrand_form', array(
            'legend'=>Mage::helper('shopbybrand')->__('Brand Information')
        ));

        $fieldset->addField('name', 'text', array(
            'label'        => Mage::helper('shopbybrand')->__('Name'),
            'class'        => 'required-entry',
            'required'    => true,
            'name'        => 'name',
            'disabled'  => ($inStore && !$data['name_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="name_default" name="name_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['name_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="name_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
			['.$scopeLabel.']
          ' : '</td><td class="scope-label">
			['.$scopeLabel.']',
        ));

        $fieldset->addField('percentage_discount', 'text', array(
            'label'        => Mage::helper('shopbybrand')->__('Percentage Discount'),
            'name'        => 'percentage_discount',
            'disabled'  => ($inStore && !$data['percentage_discount_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
            <input id="percentage_discount_default" name="percentage_discount_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['percentage_discount_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
            <label for="percentage_discount_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
            ['.$scopeLabel.']
          ' : '</td><td class="scope-label">
            ['.$scopeLabel.']',
        ));
        
        /* add by Peter */
        if(!isset($data['position_brand_store']))
            $data['position_brand_store'] = 0;
        $fieldset->addField('position_brand', 'text', array(
            'label'        => Mage::helper('shopbybrand')->__('Sort Order'),
            'name'        => 'position_brand',
            'disabled'  => ($inStore && !$data['position_brand_store']),
//            'after_element_html' => $inStore ? '</td><td class="use-default">
//			<input id="name_default" name="name_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['name_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
//			<label for="name_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
//          </td><td class="scope-label">
//			['.$scopeLabel.']
//          ' : '</td><td class="scope-label">
//			['.$scopeLabel.']',
            'after_element_html' => '<br><small>Brand with smaller sort order value will come first in Brand Listing page. </small>',
        ));
        /* end add by Peter */
        
        $fieldset->addField('url_key', 'text', array(
            'label'        => Mage::helper('shopbybrand')->__('URL Key'),
            'required'    => true,
            'name'        => 'url_key',
            'disabled'  => ($inStore),
            'after_element_html' => '<br><small>URL Key of Brand Detailed page</small>',
        ));
        
        if(!isset($data['page_title_in_store']))
            $data['page_title_in_store'] = '';
        $fieldset->addField('page_title', 'text', array(
            'label'     =>  Mage::helper('shopbybrand')->__('Page Title'),
            'required'  =>  false,
            'name'      =>  'page_title',
            'disabled'  => ($inStore && !$data['page_title_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="page_title_default" name="page_title_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['page_title_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="page_title_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
			['.$scopeLabel.']
          ' : '</td><td class="scope-label">
			['.$scopeLabel.']',
            'after_element_html' => '<br><small>Title of Brand Detailed page</small>',
        ));
        
        if(isset($data['thumbnail_image']) && $data['thumbnail_image'])
        {
            $fieldset->addField('old_thumbnail_image', 'hidden', array(
                'label'     => Mage::helper('shopbybrand')->__('Current Thumbnail Image'),
                'required'  => false,
                'name'      => 'old_thumbnail_image',
                'value'     =>$data['thumbnail_image'],
            ));
         }	
        $fieldset->addField('thumbnail_image', 'image', array(
            'label'     =>  Mage::helper('shopbybrand')->__('Logo'),
            'required'  =>  false,
            'name'      =>  'thumbnail_image',
            'after_element_html' => '<br><small>
            Supported file types: .jpeg, .jpg, .gif, .png</small>',            
        ));
        
        if(isset($data['image']) && $data['image'])
        {
            $fieldset->addField('old_image', 'hidden', array(
                'label'     => Mage::helper('shopbybrand')->__('Current Image'),
                'required'  => false,
                'name'      => 'old_image',
                'value'     =>$data['image'],
            ));
        }

        $fieldset->addField('image', 'image', array(
            'label'     =>  Mage::helper('shopbybrand')->__('Banner'),
            'required'  =>  false,
            'name'      =>  'image',
            'after_element_html' => '<br><small>
            Supported file types: .jpeg, .jpg, .gif, .png</small>',
        ));

        if(isset($data['is_designer_image']) && $data['is_designer_image'])
        {
            $fieldset->addField('old_is_designer_image', 'hidden', array(
                'label'     => Mage::helper('shopbybrand')->__('Current Designer Image'),
                'required'  => false,
                'name'      => 'old_is_designer_image',
                'value'     =>$data['is_designer_image'],
            ));
        }   

        $fieldset->addField('is_designer_image', 'image', array(
            'label'     =>  Mage::helper('shopbybrand')->__('Is Designer Image'),
            'required'  =>  false,
            'name'      =>  'is_designer_image',
            'after_element_html' => '<br><small>
            Supported file types: .jpeg, .jpg, .gif, .png</small>',
        ));
        
        if(!isset($data['banner_url']))
            $data['banner_url'] = '';
        $fieldset->addField('banner_url', 'text', array(
            'label'     =>  Mage::helper('shopbybrand')->__('Banner click-through URL'),
            'required'  =>  false,
            'name'      =>  'banner_url',
            'value'     =>  $data['banner_url'],
        ));

        
        $fieldset->addField('is_featured', 'select', array(
            'label'     => Mage::helper('shopbybrand')->__('Is Featured'),
            'name'      => 'is_featured',
            'values'    => array(
                array(
                    'value'     => 1,
                    'label'     => Mage::helper('shopbybrand')->__('Yes'),
                ),

                array(
                    'value'     => 0,
                    'label'     => Mage::helper('shopbybrand')->__('No'),
                ),
            ),
            'disabled'  => ($inStore && !$data['is_featured_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="is_featured_default" name="is_featured_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['is_featured_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="is_featured_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
			['.$scopeLabel.']
          ' : '</td><td class="scope-label">
			['.$scopeLabel.']',
        ));
        
        $fieldset->addField('is_designer', 'select', array(
            'label'     => Mage::helper('shopbybrand')->__('Is Designer'),
            'name'      => 'is_designer',
            'values'    => array(
                array(
                    'value'     => 1,
                    'label'     => Mage::helper('shopbybrand')->__('Yes'),
                ),

                array(
                    'value'     => 0,
                    'label'     => Mage::helper('shopbybrand')->__('No'),
                ),
            ),
            'disabled'  => ($inStore && !$data['is_designer_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
            <input id="is_designer_default" name="is_designer_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['is_designer_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
            <label for="is_designer_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
            ['.$scopeLabel.']
          ' : '</td><td class="scope-label">
            ['.$scopeLabel.']',
        ));
         
        $fieldset->addField('is_upcoming', 'select', array(
            'label'     => Mage::helper('shopbybrand')->__('Is Upcoming'),
            'name'      => 'is_upcoming',
            'values'    => array(
                array(
                    'value'     => 1,
                    'label'     => Mage::helper('shopbybrand')->__('Yes'),
                ),

                array(
                    'value'     => 0,
                    'label'     => Mage::helper('shopbybrand')->__('No'),
                ),
            ),
            'disabled'  => ($inStore && !$data['is_upcoming_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
            <input id="is_upcoming_default" name="is_upcoming_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['is_upcoming_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
            <label for="is_upcoming_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
            ['.$scopeLabel.']
          ' : '</td><td class="scope-label">
            ['.$scopeLabel.']',
        ));

        if(!isset($data['status_in_store']))
            $data['status_in_store'] = '';
        $fieldset->addField('status', 'select', array(
            'label'        => Mage::helper('shopbybrand')->__('Status'),
            'name'        => 'status',
            'values'    => Mage::getSingleton('shopbybrand/status')->getOptionHash(),
            'disabled'  => ($inStore && !$data['status_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="status_default" name="status_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['status_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="status_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
			['.$scopeLabel.']
          ' : '</td><td class="scope-label">
			['.$scopeLabel.']',
        ));

        /* Edit by Son */
        if(!isset($data['short_description_in_store']))
            $data['short_description_in_store'] = '';
        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(array('add_variables' => false, 'add_widgets' => false,'add_images'=>false,'files_browser_window_url'=>$this->getBaseUrl().'admin/cms_wysiwyg_images/index/'));
        $fieldset->addField('short_description', 'editor', array(
            'name'        => 'short_description',
            'label'        => Mage::helper('shopbybrand')->__('Short Description'),
            'title'        => Mage::helper('shopbybrand')->__('Short Description'),
            'style'        => 'width:600px; height:100px;',
            'wysiwyg'    => true,
            'required'    => false,
            'config'        =>$wysiwygConfig,
            'disabled'  => ($inStore && !$data['short_description_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="short_description_default" name="short_description_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['short_description_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="short_description_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
			['.$scopeLabel.']
          ' : '</td><td class="scope-label">
			['.$scopeLabel.']',
            'after_element_html' => '<small>100 characters for maximum . </small>',
        ));
        
        
        
       if(!isset($data['description_in_store']))
            $data['description_in_store'] = '';
        $fieldset->addField('description', 'editor', array(
            'name'        => 'description',
            'label'        => Mage::helper('shopbybrand')->__('Description'),
            'title'        => Mage::helper('shopbybrand')->__('Description'),
            'style'        => 'width:600px; height:100px;',
            'wysiwyg'    => true,
            'required'    => false,
            'config'       => $wysiwygConfig,
            'disabled'  => ($inStore && !$data['description_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="description_default" name="description_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['description_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="description_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
			['.$scopeLabel.']
          ' : '</td><td class="scope-label">
			['.$scopeLabel.']',
            'after_element_html' => '<small>500 characters for maximum. </small>',
        ));
         /* Edit by Son */
        $fieldset->addField('meta_keywords', 'editor', array(
            'name'        => 'meta_keywords',
            'label'        => Mage::helper('shopbybrand')->__('Meta Keywords'),
            'title'        => Mage::helper('shopbybrand')->__('Meta Keywords'),
            'style'        => 'width:600px; height:100px;',
            'wysiwyg'    => false,
            'required'    => false,
            'disabled'  => ($inStore && !$data['meta_keywords_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="meta_keywords_default" name="meta_keywords_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['meta_keywords_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="meta_keywords_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
			['.$scopeLabel.']
          ' : '</td><td class="scope-label">
			['.$scopeLabel.']',
        ));
        
        $fieldset->addField('meta_description', 'editor', array(
            'name'        => 'meta_description',
            'label'        => Mage::helper('shopbybrand')->__('Meta Description'),
            'title'        => Mage::helper('shopbybrand')->__('Meta Description'),
            'style'        => 'width:600px; height:100px;',
            'wysiwyg'    => false,
            'required'    => false,
            'disabled'  => ($inStore && !$data['meta_description_in_store']),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="meta_description_default" name="meta_description_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['meta_description_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="meta_description_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
          </td><td class="scope-label">
			['.$scopeLabel.']
          ' : '</td><td class="scope-label">
			['.$scopeLabel.']',
        ));
        
		
        /* Information tab work Start */
	if(!isset($data['is_show_information_tab_in_store']))
            $data['is_show_information_tab_in_store'] = '';
            $fieldset->addField('is_show_information_tab', 'select', array(
                'label'     => Mage::helper('shopbybrand')->__('Show Information Tab'),
                'name'      => 'is_show_information_tab',
                'values'    => array(
                                array('value'     => 1,'label'     => Mage::helper('shopbybrand')->__('Yes'), ),
                                array('value'     => 0,'label'     => Mage::helper('shopbybrand')->__('No'),),
                            ),
                'disabled'  => ($inStore && !$data['is_show_information_tab_in_store']),
                            'after_element_html' => $inStore ? '</td><td class="use-default">
                            <input id="is_show_information_tab_default" name="is_show_information_tab_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['is_show_information_tab_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
                            <label for="information_title_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
              </td><td class="scope-label">
                            ['.$scopeLabel.']
              ' : '</td><td class="scope-label">
                            ['.$scopeLabel.']',
            ));
	if(!isset($data['information_title_in_store']))
            $data['information_title_in_store'] = '';
		
            $fieldset->addField('information_title', 'text', array(
                'label'     =>  Mage::helper('shopbybrand')->__('Information Tab Title'),
                'required'  =>  false,
                'name'      =>  'information_title',
                'value'     =>  $data['information_title'],
                            'disabled'  => ($inStore && !$data['information_title_in_store']),
                'after_element_html' => $inStore ? '</td><td class="use-default">
                            <input id="information_title_default" name="information_title_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['information_title_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
                            <label for="information_title_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
              </td><td class="scope-label">
                            ['.$scopeLabel.']
              ' : '</td><td class="scope-label">
                            ['.$scopeLabel.']',
            ));
		
        if(!isset($data['information_description_in_store']))
            $data['information_description_in_store'] = '';
		
            $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(array('add_variables' => false, 'add_widgets' => false,'add_images'=>false,'files_browser_window_url'=>$this->getBaseUrl().'admin/cms_wysiwyg_images/index/'));
            $fieldset->addField('information_description', 'editor', array(
                'name'        => 'information_description',
                'label'        => Mage::helper('shopbybrand')->__('Information'),
                'title'        => Mage::helper('shopbybrand')->__('Information'),
                'style'        => 'width:600px; height:100px;',
                'wysiwyg'    => true,
                'required'    => false,
                'config'        =>$wysiwygConfig,
                'disabled'  => ($inStore && !$data['information_description_in_store']),
                'after_element_html' => $inStore ? '</td><td class="use-default">
                            <input id="information_description_default" name="information_description_default" type="checkbox" value="1" class="checkbox config-inherit" '.($data['information_description_in_store'] ? '' : 'checked="checked"').' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
                            <label for="description_default" class="inherit" title="'.$defaultTitle.'">'.$defaultLabel.'</label>
              </td><td class="scope-label">
                            ['.$scopeLabel.']
              ' : '</td><td class="scope-label">
                            ['.$scopeLabel.']',
            ));
    /*  Information tab work end */
		
        if(!isset($data['brand_id']))
            $data['brand_id'] = '';
        if(isset($data['image']) && $data['image'])
        {
            $data['old_image'] =  $data['image'];
            $data['image'] =  Mage::helper('shopbybrand')->getUrlImagePath($data['brand_id']) .'/'. $data['image'];
        }

        if(isset($data['is_designer_image']) && $data['is_designer_image'])
        {
            $data['old_is_designer_image'] =  $data['is_designer_image'];
            $data['is_designer_image'] =  Mage::helper('shopbybrand')->getUrlDesignerImagePath($data['brand_id']) .'/'. $data['is_designer_image'];
        }
        
        if(isset($data['thumbnail_image']) && $data['thumbnail_image'])
        {
            $data['old_thumbnail_image'] =  $data['thumbnail_image'];
            $data['thumbnail_image'] =  Mage::helper('shopbybrand')->getUrlThumbnailImagePath($data['brand_id']) .'/'. $data['thumbnail_image'];
        }

        $form->setValues($data);
        return parent::_prepareForm();
    }
}