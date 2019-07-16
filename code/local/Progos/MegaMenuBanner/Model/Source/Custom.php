<?php
 
class Progos_MegaMenuBanner_Model_Source_Custom extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
        $options = array(
        	'' => 'Select Position',
            'custom' => 'Custom',
            'top' => 'Top',
            'bottom' => 'Bottom'
        );

        return $options;
    }
}