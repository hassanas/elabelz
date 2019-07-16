<?php

/**
 * This Module used to add option for verdors configurations
 *
 * @category       Progos
 * @package        Progos_VendorQtyUpdate
 * @copyright      Progos Tech (c) 2018
 * @Author         Hassan Ali Shahzad
 * @date           10-05-2018 15:48
 */
class Progos_VendorQtyUpdate_Helper_Config extends Mage_Core_Helper_Abstract
{

    /**
     * @param $code
     * @return array
     */
    public function getConfigurations($code)
    {
        $data = array();

        switch ($code) {
            case 'chicshoes':
                $data['path'] = Mage::getStoreConfig('progos_vendorqtyupdate/chickshopconfiguration/file_url');
                $data['emails'] = Mage::getStoreConfig('progos_vendorqtyupdate/chickshopconfiguration/emails');

                break;
            case 'ownthelook':
                $data['path'] = Mage::getStoreConfig('progos_vendorqtyupdate/ownthelookconfiguration/file_url');
                $data['emails'] = Mage::getStoreConfig('progos_vendorqtyupdate/ownthelookconfiguration/emails');

                break;
            case 'anotah':
                $data['path'] = Mage::getStoreConfig('progos_vendorqtyupdate/anothaeleganzaconfiguration/file_url');
                $data['emails'] = Mage::getStoreConfig('progos_vendorqtyupdate/anothaeleganzaconfiguration/emails');
                break;
            default:


        }
        return $data;
    }

}