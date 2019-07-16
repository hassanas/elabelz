<?php

/**
 * This Module will update Products Attribute values against Arabic and english values
 * Attributes  will be provided in CSV file as per pre defined Format
 *
 * @category       Progos
 * @package        Progos_ProductsUpdater
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           15-08-2017 12:04
 */
class Progos_ProductsUpdater_Helper_Data extends Mage_Core_Helper_Abstract
{

    /*
     *  variable which store allowed files
     *
     * */
    protected $allowedExtensions = array('csv');

    /**
     * @param $attr Is attributes List
     * this function will verify this list is valid attributes
     *
     */
    public function isValidAttributeList($attrs)
    {
        $validPrimaryElements = array("id", "sku");
        reset($attrs);
        $first = key($attrs);
        if (!in_array($attrs[$first], $validPrimaryElements)) {
            return false;
        }
        foreach ($attrs as $key => $attr) {
            if ($first == $key) continue;
            if (null === Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', trim($attr))->getId()) {
                return false;
            }
        }
        return true;
    }

    /**
     * verify uploaded files extension is valid
     */
    public function allowedExtension()
    {
        $ext = pathinfo($_FILES['import_attribute_file']['name'], PATHINFO_EXTENSION);
        if (!in_array($ext, $this->allowedExtensions)) {
            return false;
        } else {
            return true;
        }
    }
}