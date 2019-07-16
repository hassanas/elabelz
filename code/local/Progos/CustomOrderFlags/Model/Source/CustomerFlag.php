<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Touqeer Jalal <touqeer.jalal@progos.org>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
class Progos_CustomOrderFlags_Model_Source_CustomerFlag
{
    public function toOptionArray($isFilter=false)
    {
		if(!$isFilter)
		{
			return array(
				'' => Mage::helper('customorderflags')->__('-- Please Select --'),
				'1' => Mage::helper('customorderflags')->__('VIP customer'),
				'2' => Mage::helper('customorderflags')->__('Upset Customer'),
			);
		}
		else
		{
			return array(
				'1' => Mage::helper('customorderflags')->__('VIP customer'),
				'2' => Mage::helper('customorderflags')->__('Upset Customer'),
			);
		}
    }
}