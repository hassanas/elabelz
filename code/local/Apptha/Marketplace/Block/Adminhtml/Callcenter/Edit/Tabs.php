<?php

class Apptha_Marketplace_Block_Adminhtml_Callcenter_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('callcenter_tabs');
		$this->setTitle(Mage::helper('marketplace')->__('Call Center'));
	}

	protected function _beforeToHtml()
	{
		$this->addTab('morning', array(
			'label'     => Mage::helper('marketplace')->__('Calls to attempt in Morning'),
			// 'content'   => $this->getLayout()->createBlock('marketplace/adminhtml_callcenter_edit_tab_morning')->setTitle("Morning")->toHtml()
			'url'       => $this->getUrl('*/*/morning', array('_current' => true)),
			'class'     => 'ajax only'			
		));
		
		$this->addTab('afternoon', array(
			'label'     => Mage::helper('marketplace')->__('Calls to attempt in Afternoon'),
			'url'       => $this->getUrl('*/*/afternoon', array('_current' => true)),
			'class'     => 'ajax only'
		));

		$this->addTab('evening', array(
			'label'     => Mage::helper('marketplace')->__('Calls to attempt in Evening'),
			'url'       => $this->getUrl('*/*/evening', array('_current' => true)),
			'class'     => 'ajax only'
		));

		$this->addTab('threeplus', array(
			'label'     => Mage::helper('marketplace')->__('3+ Calls attempted'),
			'url'       => $this->getUrl('*/*/plus', array('_current' => true)),
			'class'     => 'ajax only'
		));

		$this->addTab('onhold', array(
			'label'     => Mage::helper('marketplace')->__('On Hold after Customer Confirm'),
			'url'       => $this->getUrl('*/*/onhold', array('_current' => true)),
			'class'     => 'ajax only'
		));

		return parent::_beforeToHtml();
	}

}