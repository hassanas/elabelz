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
* @package     Apptha_Banners
* @version     0.1.0
* @author      Apptha Team <developers@contus.in>
* @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
* @license     http://www.apptha.com/LICENSE.txt
*
*/

class Apptha_Banners_Block_Adminhtml_Slides_Edit  extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
	{
		parent::__construct();
		
		$this->_controller = 'adminhtml_slides';
		$this->_blockGroup = 'banners';
		$this->_headerText = $this->_getHeaderText();
		
		$this->_addButton('save_and_edit_button', array(
			'label'     => Mage::helper('catalog')->__('Save and Continue Edit'),
			'onclick'   => 'editForm.submit(\''.$this->getSaveAndContinueUrl().'\')',
			'class' => 'save'
		));
	}
	
	/**
	 * Retrieve the URL used for the save and continue link
	 * This is the same URL with the back parameter added
	 *
	 * @return string
	 */
	public function getSaveAndContinueUrl()
	{
		return $this->getUrl('*/*/save', array(
			'_current'   => true,
			'back'       => 'edit',
		));
	}
    
    /**
     * Retrieve the header text
     * If splash page exists, use name
     *
     * @return string
     */
	protected function _getHeaderText()
	{
		if ($group = Mage::registry('banners_slides')) {
			if ($displayName = $group->getTitle()) {
				return $displayName;
			}
		}
	
		return $this->__('Edit Banner Slides');
	}
}
