<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Block_Adminhtml_Email_Preview extends Mage_Adminhtml_Block_Widget_Form_Container
{
	/**
	 * Add container buttons
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_controller = 'adminhtml_email';
		$this->_blockGroup = 'simpleweb_emailpreview';
		$this->_mode = 'preview';

		$this->_removeButton('reset');
		$this->_removeButton('save');
		$this->_removeButton('back');

		if (Mage::getStoreConfig('simpleweb_emailpreview/installed'))
		{
			$this->_addButton('uninstall', array(
				'label'     => Mage::helper('simpleweb_emailpreview')->__('Uninstall Test Data'),
				'class'     => 'button_uninstall delete',
				'id'        => 'button_uninstall',
			), 1);
		}
		else
		{
			$this->_addButton('install', array(
				'label'     => Mage::helper('simpleweb_emailpreview')->__('Install Test Data'),
				'class'     => 'button_install',
				'id'        => 'button_install',
			), 1);
		}

		$this->_addButton('preview', array(
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Preview in Browser'),
			'class'     => 'button_preview',
		    'id'        => 'button_preview',
		), 1);

		$this->_addButton('send', array(
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Send Test Email'),
			'class'     => 'button_send',
			'id'        => 'button_send',
		), 1);
	}

	/**
	 * Return custom title
	 *
	 * @return string
	 */
	public function getHeaderText()
	{
		return Mage::helper('simpleweb_emailpreview')->__('Preview Transactional Emails');
	}
}