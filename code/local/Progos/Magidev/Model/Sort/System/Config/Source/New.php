<?php
class Progos_Magidev_Model_Sort_System_Config_Source_New
{
	public function toOptionArray()
	{
		return array(
				array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Display top of the list')),
				array('value' => 2, 'label' => Mage::helper('adminhtml')->__('Display end of the list')),
		);
	}

	/**
	 * Get options in "key-value" format
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array(
				1 => Mage::helper('adminhtml')->__('Display top of the list'),
				2 => Mage::helper('adminhtml')->__('Display end of the list'),
		);
	}
}