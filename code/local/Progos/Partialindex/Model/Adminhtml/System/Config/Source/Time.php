<?php


class Progos_Partialindex_Model_Adminhtml_System_Config_Source_Time 
{
	
    public function toOptionArray()
    {        
        $result = array();

        for ($i = 0; $i < 24; $i++) {
            $hour = $i;
            $suffix = ' AM';
            if ($hour > 12) {
                $hour -= 12;
                $suffix = ' PM';
            } 

            if ($hour < 10) {
                $hour = '0'.$hour;
            }

            $result[] = array(
                'label' => $hour.':00'.$suffix,
                'value' => $i * 60,
            );
            $result[] = array(
                'label' => $hour.':30'.$suffix,
                'value' => $i * 60 + 30,
            );
        }

        return $result;
    }
}
