<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Saroop Chand <saroop.chand@progos.org>
 * @copyright   Copyright (c) 2018 Progos, Ltd (http://progos.org)
 */
class Progos_CustomOrderFlags_Model_Source_Aramexstatus
{
    public function toOptionArray($isFilter=false)
    {
		if(!$isFilter)
		{
            return array(
                '' => Mage::helper('customorderflags')->__('-- Please Select --'),
                '1' => Mage::helper('customorderflags')->__('delivered'),
            );
		}
		else
		{
            return array(
                '1' => Mage::helper('customorderflags')->__('delivered'),
            );
		}
    }
}