<?php

class Progos_Skuvault_Model_System_Config_Source_Updatetype {
	public function toOptionArray() {
        return [
            [
                'value' => 'disable',
                'label' => 'Disable',
            ],
            [
                'value' => 'csv',
                'label' => 'CSV',
            ],
            [
                'value' => 'api',
                'label' => 'API',
            ]
        ];
    }
}