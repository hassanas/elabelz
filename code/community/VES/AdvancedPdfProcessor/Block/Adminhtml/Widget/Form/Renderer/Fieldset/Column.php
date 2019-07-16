<?php
class VES_AdvancedPdfProcessor_Block_Adminhtml_Widget_Form_Renderer_Fieldset_Column
    extends Mage_Adminhtml_Block_Widget implements Varien_Data_Form_Element_Renderer_Interface
{
	/**
	 * Form element instance
	 *
	 * @var Varien_Data_Form_Element_Abstract
	 */
	protected $_element;
	
	/**
	 * Render HTML
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string
	 */
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		$vesCoreHelper = Mage::helper('ves_core');
		$this->setElement($element);
		return $this->toHtml();
	}
	
	/**
	 * Set form element instance
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Group_Abstract
	 */
	public function setElement(Varien_Data_Form_Element_Abstract $element)
	{
		$this->_element = $element;
		return $this;
	}
	
	/**
	 * Retrieve form element instance
	 *
	 * @return Varien_Data_Form_Element_Abstract
	 */
	public function getElement()
	{
		return $this->_element;
	}
	
	/**
	 * Prepare group price values
	 *
	 * @return array
	 */
	public function getValues()
	{
		$data = $this->getElement()->getValue();
		$vesCoreHelper = Mage::helper('ves_core');
		return $data;
	}
	
	public function getEditor() {
		return $this->getElement()->getEditor();
	}
	
	public function processEditor() {
		$editor = $this->getEditor();
		$data = explode('_', $editor);
		return $data[0] . '_item';
	}
	
	public function getInitialOptions() {
		$groups = new Varien_Object();
		$category = Mage::getModel('advancedpdfprocessor/category')->load($this->processEditor(),'code')->getData();
		$variable = Mage::getModel('advancedpdfprocessor/variable')->getCollection()
		->addFieldToFilter('category_id',$category['category_id'])->getData();
		$vesCoreHelper = Mage::helper('ves_core');
		$variable= new Varien_Object($variable);
		
		$groups->setData('item',array('label'=>'Item', 'value' => $variable->getData()));
		Mage::dispatchEvent('ves_advancedpdfprocessor_init_item_attribute_after',array('attributes'=>$groups));
		
		return $groups;
	}
	
	/**
	 * Retrieve 'add group price item' button HTML
	 *
	 * @return string
	 */
	public function getAddButtonHtml()
	{
		return $this->getChildHtml('add_button');
	}
	
    /**
     * Initialize block
     */
    public function __construct()
    {
    	$vesCoreHelper = Mage::helper('ves_core');
        $this->setTemplate('ves_advancedpdfprocessor/widget/form/renderer/fieldset/column.phtml');
    }

    /**
     * Prepare global layout
     * Add "Add tier" button to layout
     *
     * @return VES_AdvancedPdfProcessor_Block_Adminhtml_Widget_Form_Renderer_Fieldset_Column
     */
    protected function _prepareLayout()
    {
    	$vesCoreHelper = Mage::helper('ves_core');
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label' => Mage::helper('advancedpdfprocessor')->__('Add Column'),
                'class' => 'add easypdf-add'
            ));
        $button->setName('add_column_item_button');

        $this->setChild('add_button', $button);
        return parent::_prepareLayout();
    }

    
    /**
     * get option type option
     */
    public function getOptionType() {    	
    	$type = new VES_AdvancedPdfProcessor_Model_Source_Widget_Optiontype();
    	return $type->toOptionArray();
    }
}
