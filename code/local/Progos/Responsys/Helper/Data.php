<?php
/**
 * Progos_Responsys.
 *
 * @category Elabelz
 *
 * @Author Hassan Ali Shahzad <hassan.ali@progos.org>
 * @Date 29 -06-2018
 *
 */
class Progos_Responsys_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @param $value
     * @return string
     */
    public function getGender($value){
        switch( $value ){
            case '1':
                $gender = 'Male';
                break;
            case '2':
                $gender = 'Female';
                break;
            default:
                $gender = '';
        }
        return $gender;
    }
}