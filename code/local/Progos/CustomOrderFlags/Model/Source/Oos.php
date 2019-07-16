<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Touqeer Jalal <touqeer.jalal@progos.org>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
class Progos_CustomOrderFlags_Model_Source_Oos
{
    public function toOptionArray($isFilter=false)
    {
		if(!$isFilter)
		{
			return array(
				'' => Mage::helper('customorderflags')->__('-- Please Select --'),
				'1' => Mage::helper('customorderflags')->__('Ready to Ship: ops waiting'),
				'2' => Mage::helper('customorderflags')->__('Confirmed by CC'),
				'3' => Mage::helper('customorderflags')->__('Email Sent to Customer'),
			);
		}
		else
		{
			return array(
				'1' => Mage::helper('customorderflags')->__('Ready to Ship: ops waiting'),
				'2' => Mage::helper('customorderflags')->__('Confirmed by CC'),
				'3' => Mage::helper('customorderflags')->__('Email Sent to Customer'),
			);

		}
    }
}