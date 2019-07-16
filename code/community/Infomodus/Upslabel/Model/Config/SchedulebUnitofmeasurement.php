<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 07.02.12
 * Time: 10:49
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Model_Config_SchedulebUnitofmeasurement
{
    public function toOptionArray()
    {
        $array = array(
            array('value' => 'BBL', 'label' => 'Barrels',),
            array('value' => 'CAR', 'label' => 'Carat',),
            array('value' => 'CKG', 'label' => 'Content Kilogram',),
            array('value' => 'CM2', 'label' => 'Square Centimeters',),
            array('value' => 'CTN', 'label' => 'Content Ton',),
            array('value' => 'CUR', 'label' => 'Curie',),
            array('value' => 'CYK', 'label' => 'Clean Yield Kilogram',),
            array('value' => 'DOZ', 'label' => 'Dozen',),
            array('value' => 'DPC', 'label' => 'Dozen Pieces',),
            array('value' => 'DPR', 'label' => 'Dozen Pairs',),
            array('value' => 'FBM', 'label' => 'Fiber Meter',),
            array('value' => 'GCN', 'label' => 'Gross Containers',),
            array('value' => 'GM', 'label' => 'Gram',),
            array('value' => 'GRS', 'label' => 'Gross',),
            array('value' => 'HUN', 'label' => 'Hundred',),
            array('value' => 'KG', 'label' => 'Kilogram',),
            array('value' => 'KM3', 'label' => '1,000 Cubic Meters',),
            array('value' => 'KTS', 'label' => 'Kilogram Total Sugars',),
            array('value' => 'L', 'label' => 'Liter',),
            array('value' => 'M', 'label' => 'Meter',),
            array('value' => 'M2', 'label' => 'Square Meters',),
            array('value' => 'M3', 'label' => 'Cubic Meters',),
            array('value' => 'MC', 'label' => 'Millicurie',),
            array('value' => 'NO', 'label' => 'Number',),
            array('value' => 'PCS', 'label' => 'Pieces',),
            array('value' => 'PFL', 'label' => 'Proof Liter',),
            array('value' => 'PK', 'label' => 'Pack',),
            array('value' => 'PRS', 'label' => 'Pairs',),
            array('value' => 'RBA', 'label' => 'Running Bales',),
            array('value' => 'SQ', 'label' => 'Square',),
            array('value' => 'T', 'label' => 'Ton',),
            array('value' => 'THS', 'label' => '1,000',),
            array('value' => 'X', 'label' => 'No Quantity required',),
        );
        return $array;
    }

    public function getScheduleUnitName($key)
    {
        $array = array(
            'BBL' => 'Barrels',
            'CAR' => 'Carat',
            'CKG' => 'Content Kilogram',
            'CM2' => 'Square Centimeters',
            'CTN' => 'Content Ton',
            'CUR' => 'Curie',
            'CYK' => 'Clean Yield Kilogram',
            'DOZ' => 'Dozen',
            'DPC' => 'Dozen Pieces',
            'DPR' => 'Dozen Pairs',
            'FBM' => 'Fiber Meter',
            'GCN' => 'Gross Containers',
            'GM' => 'Gram',
            'GRS' => 'Gross',
            'HUN' => 'Hundred',
            'KG' => 'Kilogram',
            'KM3' => '1,000 Cubic Meters',
            'KTS' => 'Kilogram Total Sugars',
            'L' => 'Liter',
            'M' => 'Meter',
            'M2' => 'Square Meters',
            'M3' => 'Cubic Meters',
            'MC' => 'Millicurie',
            'NO' => 'Number',
            'PCS' => 'Pieces',
            'PFL' => 'Proof Liter',
            'PK' => 'Pack',
            'PRS' => 'Pairs',
            'RBA' => 'Running Bales',
            'SQ' => 'Square',
            'T' => 'Ton',
            'THS' => '1,000',
            'X' => 'No Quantity required',
        );
        return isset($array[$key])?$array[$key]:'';
    }
}
