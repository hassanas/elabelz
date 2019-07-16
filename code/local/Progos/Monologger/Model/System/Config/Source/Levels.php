<?php
class Progos_Monologger_Model_System_Config_Source_Levels
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
		
            array('value' => 600, 'label'=>Mage::helper('monologger')->__('EMERGENCY')),
            array('value' => 550, 'label'=>Mage::helper('monologger')->__('ALERT')),
            array('value' => 500, 'label'=>Mage::helper('monologger')->__('CRITICAL')),
            array('value' => 400, 'label'=>Mage::helper('monologger')->__('ERROR')),
            array('value' => 300, 'label'=>Mage::helper('monologger')->__('WARNING')),
            array('value' => 250, 'label'=>Mage::helper('monologger')->__('NOTICE')),
            array('value' => 200, 'label'=>Mage::helper('monologger')->__('INFO')),
            array('value' => 100, 'label'=>Mage::helper('monologger')->__('DEBUG')),
        );
    }
}
