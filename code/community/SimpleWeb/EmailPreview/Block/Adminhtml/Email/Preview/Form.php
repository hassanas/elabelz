<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Block_Adminhtml_Email_Preview_Form
	extends Mage_Adminhtml_Block_Widget_Form
{
	/**
	 * @return Mage_Adminhtml_Block_Widget_Form
	 */
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form(array(
			'id' => 'edit_form',
			'action' => $this->getUrl('*/*/preview'),
			'method' => 'post',
		));

		$form->setHtmlIdPrefix('emailpreview_');

		$fieldset = $form->addFieldset(
			'base_fieldset',
			array('legend' =>
				Mage::helper('simpleweb_emailpreview')->__('Select template')
			)
		);

		$fieldset->addField('default-store-id', 'hidden', array(
			'id'        => 'default-store-id',
			'value'     => Mage::app()->getDefaultStoreView()->getId(),
		));

		$fieldset->addField('ajax-url-variables', 'hidden', array(
			'id'        => 'ajax-url-variables',
		    'value'     => $this->getUrl('*/*/getvariables'),
		));

		$fieldset->addField('ajax-url-customer', 'hidden', array(
			'id'        => 'ajax-url-customer',
			'value'     => $this->getUrl('*/*/changecustomer'),
		));

		$fieldset->addField('ajax-url-order', 'hidden', array(
			'id'        => 'ajax-url-order',
			'value'     => $this->getUrl('*/*/changeorder'),
		));

		$fieldset->addField('ajax-url-send', 'hidden', array(
			'id'        => 'ajax-url-send',
			'value'     => $this->getUrl('*/*/send'),
		));

		$fieldset->addField('ajax-url-install', 'hidden', array(
			'id'        => 'ajax-url-install',
			'value'     => $this->getUrl('*/*/install'),
		));

		$fieldset->addField('ajax-url-uninstall', 'hidden', array(
			'id'        => 'ajax-url-uninstall',
			'value'     => $this->getUrl('*/*/uninstall'),
		));

		$fieldset->addField('recipient', 'text', array(
			'name'      => 'recipient',
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Recipient\'s email'),
			'title'     => Mage::helper('simpleweb_emailpreview')->__('Recipient\'s email'),
			'required'  => true,
		));

		$fieldset->addField('template', 'select', array(
			'name'      => 'template_id',
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Transaction email template'),
			'title'     => Mage::helper('simpleweb_emailpreview')->__('Transaction email template'),
			'required'  => true,
			'options'   => $this->_getEmailTemplates(),
		));

		$fieldset = $form->addFieldset(
			'object_fieldset',
			array('legend' =>
				Mage::helper('simpleweb_emailpreview')->__('Default Data')
			)
		);

		$fieldset->addField('customer', 'select', array(
			'name'      => 'customer_id',
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Customer'),
			'title'     => Mage::helper('simpleweb_emailpreview')->__('Customer'),
			'options'   => $this->_getCustomerList(),
		));

		$fieldset->addField('wishlist', 'select', array(
			'name'      => 'wishlist_id',
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Wishlist'),
			'title'     => Mage::helper('simpleweb_emailpreview')->__('Wishlist'),
		));

		$fieldset->addField('order', 'select', array(
			'name'      => 'order_id',
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Order'),
			'title'     => Mage::helper('simpleweb_emailpreview')->__('Order'),
		));

		$fieldset->addField('invoice', 'select', array(
			'name'      => 'invoice_id',
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Invoice'),
			'title'     => Mage::helper('simpleweb_emailpreview')->__('Invoice'),
		));

		$fieldset->addField('shipment', 'select', array(
			'name'      => 'shipment_id',
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Shipment'),
			'title'     => Mage::helper('simpleweb_emailpreview')->__('Shipment'),
		));

		$fieldset->addField('creditmemo', 'select', array(
			'name'      => 'creditmemo_id',
			'label'     => Mage::helper('simpleweb_emailpreview')->__('Creditmemo'),
			'title'     => Mage::helper('simpleweb_emailpreview')->__('Creditmemo'),
		));

		$form->setUseContainer(true);
		$this->setForm($form);

		return parent::_prepareForm();
	}

	/**
	 * Return customer list as options
	 *
	 * @return array
	 */
	protected function _getCustomerList()
	{
		$customers = Mage::getModel('customer/customer')
			->getCollection()
			->addAttributeToSelect(array('firstname', 'lastname', 'email'))
			->setPage(1, 500);

		if ($customerId = Mage::getStoreConfig('simpleweb_emailpreview/customer'))
		{
			$customers->getSelect()->order(array(
				new Zend_Db_Expr(sprintf('entity_id = %s DESC', $customerId)),
				'entity_id ASC'
			));
		}

		$options = array();
		foreach($customers as $customer)
		{
			$options[$customer->getId()] = $customer->getName() . ' [' . $customer->getEmail() . ']';
		}

		return $options;
	}

	/**
	 * Return email templates as options list
	 *
	 * @return array
	 */
	protected function _getEmailTemplates()
	{
		$templates = array('' => '');

		$userTemplates = Mage::getModel('core/email_template')->getCollection();
		foreach($userTemplates as $template)
		{
			$templates[$template->getId()] = $template->getTemplateCode();
		}

		$defaultTemplates = Mage_Core_Model_Email_Template::getDefaultTemplatesAsOptionsArray();
		foreach($defaultTemplates as $template)
		{
			if (!empty($template['label']))
			{
				$templates[$template['value']] = '[Default] ' . $template['label'];
			}
		}

		return $templates;
	}
}