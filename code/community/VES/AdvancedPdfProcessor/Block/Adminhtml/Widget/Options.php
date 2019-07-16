<?php

/**
 * WYSIWYG widget options form
 *
 * @category   Mage
 * @package    Mage_Widget
 * @author     Magento Core Team <core@magentocommerce.com>
 */

class VES_AdvancedPdfProcessor_Block_Adminhtml_Widget_Options extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Element type used by default if configuration is omitted
     * @var string
     */
    protected $_defaultElementType = 'text';

    /**
     * Translation helper instance, defined by the widget type declaration root config node
     * @var Mage_Core_Helper_Abstract
     */
    protected $_translationHelper = null;

    /**
     * Prepare Widget Options Form and values according to specified type
     *
     * widget_type must be set in data before
     * widget_values may be set before to render element values
     */
    protected function _prepareForm()
    {
        $this->getForm()->setUseContainer(false);
        $form = $this->getForm();
        $data = $this->getWidgetValues();
        $vesCoreHelper = Mage::helper('ves_core');
        
        $fieldset = $this->getMainFieldset();
		foreach(array('header','row') as $item) {
			$item == 'header' ? $label = 'Table Header' : $label = 'Item Row';
			
			$fieldset->addField($item.'_heading', 'text', array(
		          'label'     => Mage::helper('advancedpdfprocessor')->__($label),
        	));
			//set renderer
			$form->getElement($item.'_heading')->setRenderer(
            	$this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_widget_form_renderer_fieldset_heading')
			);
			
			$fieldset->addField($item.'_font_family', 'select', array(
		          'label'     => Mage::helper('advancedpdfprocessor')->__('Font family'),
		          'class'     => 'required-entry',
		          'required'  => true,
		          'name'      => 'parameters['.$item.'_font_family]',
				  'values'	  => VES_AdvancedPdfProcessor_Model_Source_Data::toOptionArray(VES_AdvancedPdfProcessor_Model_Source_Data::FONT_FAMILY),
				  'value'	  => $data[$item.'_font_family'],	
        	));
			//set renderer
			$form->getElement($item.'_font_family')->setRenderer(
					$this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_widget_form_renderer_fieldset_element')
			);
			
			$fieldset->addField($item.'_font_size', 'select', array(
					'label'     => Mage::helper('advancedpdfprocessor')->__('Font size'),
					'class'     => 'required-entry',
					'required'  => true,
					'name'      => 'parameters['.$item.'_font_size]',
				  	'values'	  => VES_AdvancedPdfProcessor_Model_Source_Data::toOptionArray(VES_AdvancedPdfProcessor_Model_Source_Data::FONT_SIZE),
					'value'	  => $data[$item.'_font_size'],
			));
			//set renderer
			$form->getElement($item.'_font_size')->setRenderer(
					$this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_widget_form_renderer_fieldset_element')
			);
			
			$fieldset->addField($item.'_font_italic', 'select', array(
					'label'     => Mage::helper('advancedpdfprocessor')->__('Italic'),
					'name'      => 'parameters['.$item.'_font_italic]',
					'values'	  => VES_AdvancedPdfProcessor_Model_Source_Data::toOptionArray(VES_AdvancedPdfProcessor_Model_Source_Data::FONT_ITALIC),
					'value'	  => isset($data[$item.'_font_italic'])?$data[$item.'_font_italic']:'0',
			));
			//set renderer
			$form->getElement($item.'_font_italic')->setRenderer(
					$this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_widget_form_renderer_fieldset_element')
			);
			
			$fieldset->addField($item.'_font_bold', 'select', array(
					'label'     => Mage::helper('advancedpdfprocessor')->__('Bold'),
					'name'      => 'parameters['.$item.'_font_bold]',
					'values'	  => VES_AdvancedPdfProcessor_Model_Source_Data::toOptionArray(VES_AdvancedPdfProcessor_Model_Source_Data::FONT_BOLD),
					'value'	  => isset($data[$item.'_font_bold'])?$data[$item.'_font_bold']:0,
			));
			//set renderer
			$form->getElement($item.'_font_bold')->setRenderer(
					$this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_widget_form_renderer_fieldset_element')
			);
		}
		//for column
		$fieldset->addField('column_heading', 'text', array(
				'label'     => Mage::helper('advancedpdfprocessor')->__('Columns'),
		));
		//set renderer
		$form->getElement('column_heading')->setRenderer(
				$this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_widget_form_renderer_fieldset_heading')
		);
		
		$fieldset->addField('column', 'text', array(
				'name'=>'column',
				'value'	  => $data['column'],
				'editor'	=> $this->getEditor(),
		));
		
		$form->getElement('column')->setRenderer(
				$this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_widget_form_renderer_fieldset_column')
		);
        return $this;
    }

    /**
     * Form getter/instantiation
     *
     * @return Varien_Data_Form
     */
    public function getForm()
    {
    	$vesCoreHelper = Mage::helper('ves_core');
        if ($this->_form instanceof Varien_Data_Form) {
            return $this->_form;
        }
        $form = new Varien_Data_Form();
        $this->setForm($form);
        return $form;
    }

    /**
     * Fieldset getter/instantiation
     *
     * @return Varien_Data_Form_Element_Fieldset
     */
    public function getMainFieldset()
    {
        if ($this->_getData('main_fieldset') instanceof Varien_Data_Form_Element_Fieldset) {
            return $this->_getData('main_fieldset');
        }
        $vesCoreHelper = Mage::helper('ves_core');
        $mainFieldsetHtmlId = 'options_fieldset' . md5($this->getWidgetType());
        $this->setMainFieldsetHtmlId($mainFieldsetHtmlId);
        $fieldset = $this->getForm()->addFieldset($mainFieldsetHtmlId, array(
            'legend'    => $this->helper('widget')->__('Widget Options'),
            'class'     => 'fieldset-wide',
        ));
        $this->setData('main_fieldset', $fieldset);

        // add dependence javascript block
        $block = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence');
        $this->setChild('form_after', $block);

        return $fieldset;
    }
}
