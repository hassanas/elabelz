<?php
/**
 *
 * @author		VnEcoms Team <support@vnecoms.com>
 * @website		http://www.vnecoms.com
 */
class VES_AdvancedPdfProcessor_Model_Observer
{
	/**
	 * Change some text of easy pdf invoice extension
	 * @param Varien_Event_Observer $observer
	 */
	public function ves_pdfpro_apikey_tabs_after(Varien_Event_Observer $observer){
		$block = $observer->getBlock();
		$vesHelper = Mage::helper('ves_core');
		$block->addTabAfter('css', array(
  			'label'     => Mage::helper('advancedpdfprocessor')->__('Additional Css'),
  			'title'     => Mage::helper('advancedpdfprocessor')->__('Additional Css'),
  			'content'   => $block->getLayout()->createBlock('advancedpdfprocessor/adminhtml_key_edit_tab_css')->toHtml(),
  		),'form');
		$block->addTabAfter('template', array(
				'label'     => Mage::helper('advancedpdfprocessor')->__('Design'),
				'title'     => Mage::helper('advancedpdfprocessor')->__('Design'),
				'content'   => $block->getLayout()->createBlock('advancedpdfprocessor/adminhtml_key_edit_tab_template')->toHtml(),
		),'form');
		$block->setTabData('form','label',Mage::helper('advancedpdfprocessor')->__('PDF Template Information'));
		$block->setTabData('form','title',Mage::helper('advancedpdfprocessor')->__('PDF Template Information'));
		$block->setTitle(Mage::helper('advancedpdfprocessor')->__('PDF Template Information'));
	}
	
	/**
	 * Change some text on grid of Easy PDF Invoice extension
	 * @param Varien_Event_Observer $observer
	 */
	public function ves_pdfpro_grid_prepare_columns_after(Varien_Event_Observer $observer){
		$vesHelper = Mage::helper('ves_core');
		$block = $observer->getBlock();
		$block->getColumn('api_key')->setData('header',Mage::helper('advancedpdfprocessor')->__('Identifier'));
	}
	
	/**
	 * Add new logo column to API Key grid
	 * @param Varien_Event_Observer $observer
	 */
	public function core_block_abstract_prepare_layout_before(Varien_Event_Observer $observer){
		$vesCoreHelper = Mage::helper('ves_core');
		$block = $observer->getEvent()->getBlock();
		if($block instanceof VES_PdfPro_Block_Adminhtml_Key_Grid){
			$block->addColumnAfter('logo',
	            array(
	                'header'	=> Mage::helper('advancedpdfprocessor')->__('Logo'),
	                'index' 	=> 'logo',
	            	'renderer' 	=> new VES_AdvancedPdfProcessor_Block_Adminhtml_Grid_Renderer_Image(),
	            	'sortable'	=> false,
	            	'type'      => 'string',
	            	'width'		=> '100px',
		        ),
		        'entity_id'
        	);
		}elseif($block instanceof VES_PdfPro_Block_Adminhtml_Key_Edit){
			$block->updateButton('save', 'label', Mage::helper('advancedpdfprocessor')->__('Save Template'));
        	$block->updateButton('delete', 'label', Mage::helper('advancedpdfprocessor')->__('Delete Template'));
		}elseif($block instanceof VES_PdfPro_Block_Adminhtml_Key){
			$block->setHeaderText(Mage::helper('advancedpdfprocessor')->__('PDF Template Manager'));
			$block->updateButton('add','label',Mage::helper('advancedpdfprocessor')->__('Add Template'));
		}
    }
    
    /**
     * Add Logo field to field set
     * @param Varien_Event_Observer $observer
     */
    public function ves_pdfpro_apikey_form_prepare_after(Varien_Event_Observer $observer){
    	$fieldset = $observer->getFieldset();
    	$vesHelper = Mage::helper('ves_core');
    	$fieldset->addField('logo', 'image', array(
    			'label'     => Mage::helper('pdfpro')->__('Logo'),
    			'class'     => 'required-entry',
    			'required'  => true,
    			'name'      => 'logo',
    	),'api_key');
    	$fieldset->setData('legend', Mage::helper('pdfpro')->__(''));
    	$elm = $fieldset->getForm()->getElement('api_key');
    	$elm->setData('label',Mage::helper('advancedpdfprocessor')->__('Identifier'));
    	$elm->setData('class','validate-xml-identifier');
    }
    
    /**
     * Save the css, template to database
     * @param unknown_type $observer
     */
    function ves_pdfpro_apikey_form_save_before(Varien_Event_Observer $observer){
    	$model = $observer->getModel();
    	$data = Mage::app()->getFrontController()->getRequest()->getParams();
		$vesCoreHelper = Mage::helper('ves_core');
    	$model->setData('css', $data['css'])
    	->setData('template_id', $data['template_id'])
    	->setData('order_template', $data['order_template'])
    	->setData('invoice_template', $data['invoice_template'])
    	->setData('shipment_template', $data['shipment_template'])
    	->setData('creditmemo_template', $data['creditmemo_template']);
    	
    	if(isset($_FILES['logo']['name']) && $_FILES['logo']['name'] != '') {
    		try {
    			/* Starting upload */
    			$uploader = new Varien_File_Uploader('logo');
    			// Any extention would work
    			$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
    			$uploader->setAllowRenameFiles(true);
    			$uploader->setFilesDispersion(false);
    			$path = Mage::getBaseDir('media') . DS .'ves_pdfpro'.DS.'logos'.DS ;
    			$uploader->save($path, $_FILES['logo']['name'] );
    	
    		} catch (Exception $e) {
    			 
    		}
    		//this way the name is saved in DB
    		$model->setData('logo','ves_pdfpro/logos/'.$uploader->getUploadedFileName());
    	}else{
    		$logoData = $model->getLogo();
    		if(isset($logoData['delete']) && $logoData['delete']){
    			$model->setLogo('');
    		}else{
    			$model->setLogo($logoData['value']);
    		}
    	}
    }
    
    public function core_block_abstract_to_html_after($observer = NULL)
    {
    	if (!$observer) {
    		return;
    	}
    	$vesCoreHelper = Mage::helper('ves_core');
    	if(Mage::app()->getRequest()->getRouteName() == 'pdfpro_cp' 
    		&& Mage::app()->getRequest()->getControllerName() == 'adminhtml_key'
    		&& Mage::app()->getRequest()->getActionName() == 'edit') {
	    	if ('before_body_end' == $observer->getEvent()->getBlock()->getNameInLayout()) {
	    		$transport = $observer->getEvent()->getTransport();
	    		$block = new VES_AdvancedPdfProcessor_Block_Javascript();
	    		$block->setPassingTransport($transport['html']);
	    		$block->toHtml();
	    	}
    	}
    	return $this;
    }
    
    /*
     * process shipment tracking
     */
    public function ves_pdfpro_data_prepare_after($observer){
    $type = $observer->getType();
    	$vesHelper = Mage::helper('ves_core');
    	if(in_array($type, array('order','invoice','shipment','creditmemo'))){
    		$source = $observer->getSource();
    		$model 	= $observer->getModel();
    		$class 	= $type=='order'?'pdfpro/order':'pdfpro/order_'.$type;
			$order	= $type=='order'?$model:$model->getOrder();
			$precision				= Mage::getStoreConfig('pdfpro/config/number_format')!==''?Mage::getStoreConfig('pdfpro/config/number_format'):2;
			
    		$pdfModel 				= Mage::getModel($class);
			$pdfModel->setTranslationByStoreId($order->getStoreId());
    		$priceAttributes 		= $pdfModel->getPriceAttributes();
    		$basePriceAttributes	= $pdfModel->getBasePriceAttributes();
    		if(is_array($basePriceAttributes)){
    			$priceAttributes		= array_merge($priceAttributes,$basePriceAttributes);
    		}
    		
    		foreach($priceAttributes as $attribute){
    			$source->setData('original_'.$attribute,Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->toCurrency($model->getData($attribute),array('precision'=>$precision,'display'=>1)));
    		}
			$pdfModel->revertTranslation();
    	}
    	if($type=='item'){
    		$itemData = $observer->getSource();
    		$item = $observer->getModel();

    		$itemData->setData('tax_amount',$itemData->getData('tax'));
    	}else if($type == 'shipment'){
    		$shipmentData 	= $observer->getSource();
    		$shipment 		= $observer->getModel();
    	
    		$trackingBlock = new Mage_Adminhtml_Block_Template;
    		$trackingBlock->setData(array('tracking'=>$shipmentData->getTracking()))->setTemplate('ves_advancedpdfprocessor/observer/tracking.phtml');

    		$shipmentData->setData('tracking_html',$trackingBlock->toHtml());
    	}else{
    		//var_dump($observer->getSource()->getCustomer());exit;
    	}
    }
    
    
    /**
     * prepare config cms wysiwyg
     * 
     */
    public function cms_wysiwyg_config_prepare(Varien_Event_Observer $observer) {
    	$vesHelper = Mage::helper('ves_core');
    	$config = $observer->getConfig();
    	$config->setData('ves_widget_images_url',Mage::helper('advancedpdfprocessor')->getCustomPlaceholderImagesBaseUrl())
    			->setData('ves_widget_placeholders',Mage::helper('advancedpdfprocessor')->getCustomAvailablePlaceholderFilenames())
    			->setData('custom_image_filename', Mage::helper('advancedpdfprocessor')->getCustomImageFileName());
    	
    	//fix directives_url and directives_url_quoted
    	$config->setData('directives_url',Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg/directive'));
    	$config->setData('directives_url_quoted', preg_quote(Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg/directive')));
    	$config->setData('files_browser_window_url', Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg_images/index'));
    	return;
    }
    
    public function ves_pdfpro_config_version(Varien_Event_Observer $observer){
    	$transport = $observer->getTransport();
    	$advancedPdfProcessor = array('label'=>Mage::helper('pdfpro')->__('PDF Invoice Pro version'),'value'=>Mage::helper('advancedpdfprocessor')->getVersion());
    	$transport->setData('advancedpdfprocessor', $advancedPdfProcessor);
    }
}