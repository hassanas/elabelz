<?php
class Progos_Sizeguide_Block_Adminhtml_Sizeguide_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
   /**
     * Init class
     */
	
	public function __construct()
	{
		$this->_blockGroup = 'sizeguide';
		$this->_controller = 'adminhtml_sizeguide';
		
		parent::__construct();
		
		$this->_updateButton('save', 'label', Mage::helper('sizeguide')->__('Save Size Guide'));
        $this->_updateButton('delete', 'label', Mage::helper('sizeguide')->__('Delete Size Guide'));
		 $this->_addButton('saveandcontinue', array(
			'label'		=> Mage::helper('sizeguide')->__('Save And Continue Edit'),
			'onclick'	=> 'saveAndContinueEdit()',
			'class'		=> 'save',
		), -100);
		$this->_formScripts[] = "
			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
		"; 
	}
	
	 /**
     * Get Header text
     *
     * @return string
     */
    public function getHeaderText()
    {  
        if( Mage::registry('sizeguide_data') && Mage::registry('sizeguide_data')->getId() ) {
            return Mage::helper('sizeguide')->__("Edit Size Guide '%s'", $this->htmlEscape(Mage::registry('sizeguide_data')->getTitle()));
        } else {
            return Mage::helper('sizeguide')->__('Add Size Guide');
        } 
    }  
  
}