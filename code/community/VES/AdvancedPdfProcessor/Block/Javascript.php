<?php

class VES_AdvancedPdfProcessor_Block_Javascript extends Mage_Core_Block_Template
{

	public function getTemplateJson() {
		$collection = Mage::getModel('advancedpdfprocessor/template')->getCollection();
		foreach($collection as $template) {
			$data[$template->getId()] = $template->getData();
			$data[$template->getId()]['css_url']	= $this->getSkinUrl($template->getCssPath());
		}
		return json_encode($data);
	}
	
	public function getTypeJSON() {
		return json_encode(array('order_template','invoice_template','shipment_template','creditmemo_template'));
	}
	
	public function getVariablesHtml() {
		//get category data
		$categories = Mage::getModel('advancedpdfprocessor/category')->getCollection()
		->addFieldToFilter('type_variable',Mage::helper('advancedpdfprocessor')->isTypeVariable())->setOrder('sort_order','asc');
		$html = '<ul class="variables-content">';
		foreach($categories as $category) {
			if(in_array(strtolower($category->getTitle()),array('invoice','credit memo','shipment'))) 
				$class = 'doc-type '. str_replace(' ','',strtolower($category->getTitle()));
			else 
				$class = '';
			$html .= '<li class="'.$class.'">';
			$html .= '<a href="javascript: void(0);" class="control">'.$category->getTitle().'</a>';
			$html .= '<ul class="variables-list" style="display: none;">';
			//get variables data
			$variables = Mage::getModel('advancedpdfprocessor/variable')->getCollection()->addFieldToFilter('category_id',$category->getId())->setOrder('sort_order','asc');
			if(sizeof($variables)) {
				foreach($variables as $variable) {
					$html .= '<li><a href="javascript: void(0);" onclick="MagentovariablePlugin.insertVariable(easyPdfVariables['.$category->getId().']['.$variable->getId().'])">' . $variable->getTitle() . '</a></li>';								
				}
			} else {
				$html .= '<li>' . Mage::helper('advancedpdfprocessor')->__('Comming soon ...') . '</li>';
			}
			$html .= '</ul>';
			$html .= '</li>';
			
		}
		$html .= '</ul>';
		return $html;
	}
	
	public function getJsonVariables() {
		return Mage::getModel('advancedpdfprocessor/variable')->getJsonVariables();
	}
	
	public function getCurrentTemplate(){
		if($apiKey = Mage::registry('key_data')){
			$template = Mage::getModel('advancedpdfprocessor/template')->load($apiKey->getTemplateId());
	      	return $template; 
      	}
      	
      	return false;
	}
	
	public function getLogoUrl(){
		if($template = Mage::registry('key_data')){
			return Mage::getBaseUrl('media').$template->getLogo();
		}
		return $this->getSkinUrl('ves_advancedpdfprocessor/images/logo_bg.gif');
	}
	
	/**
	 * Get TinyMCE Editor configuration
	 */
	public function getEditorConfigJSON(){
		//load config from default
		$config = Mage::getSingleton('cms/wysiwyg_config')->getConfig(
			array('tab_id' => 'form_section'));
		$config['add_images'] 		= true;
		$config['directives_url']	= Mage::helper('adminhtml')->getUrl('adminhtml/cms_wysiwyg/directive');//replace $this->getUrl = Mage::helper('adminhtml') to generate secret key
		$config['files_browser_window_url']	= $this->getUrl('adminhtml/cms_wysiwyg_images/index');
		$config['width']	  		= '990px';
		/*Attach CSS file from selected template*/
		$config['content_css']	= $this->getSkinUrl('ves_advancedpdfprocessor/default.css');
		if($apiKey = Mage::registry('key_data')){
			$template = Mage::getModel('advancedpdfprocessor/template')->load($apiKey->getTemplateId());
			$config['content_css']	.= ','.$this->getSkinUrl($template->getData('css_path'));
	
			/*Body class*/
			$config['body_class']	= $template->getSku();
		}
		$config["widget_window_url"] = Mage::getSingleton('adminhtml/url')->getUrl('advancedpdfprocessor/adminhtml_widget/index');
		$plugins = $config->getData('plugins');
		$plugins[0]["options"]["url"] = '';
		$plugins[0]["options"]["onclick"]["subject"] = "MagentovariablePlugin.loadChooser('{{html_id}}');";

		/*Add Easy PDF plugin*/
/*		$plugins[] = array(
			'name'		=> 'easypdf',
			'src'		=> $this->getJsUrl('ves_advancedpdfprocessor/tiny_mce/plugins/easypdf/editor_plugin.js'),
			'options'	=> array('logo_url'=>$this->getJsUrl('ves_advancedpdfprocessor/tiny_mce/plugins/easypdf/images/logo_bg.gif')),
		);
*/
		$config->setData('plugins' , $plugins);
		return Zend_Json::encode($config);
	}
}