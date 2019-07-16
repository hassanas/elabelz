<?php
class Progos_Defaultstore_Model_Defaultlang
{
     public function toOptionArray()
    {
        $result = array(
            array(
                'value' => 1,
                'label' => 'Arabic',
            ),
            array(
                'value' => 2,
                'label' => 'English',
            ),
        );

        return $result;
    }

}
