<?php 
/*
 * @category    Local
 * @package     local_Catalog
 * @author      Azhar Farooq <az.fq.jh@gmail.com>
 */
class Mage_Catalog_Model_Product_Featured extends Mage_Core_Model_Abstract
{

    const STATUS_YES = 1;
    const STATUS_NO = 0;

    const STATUS_PPENDING_REVIEW  = '1024';
    const STATUS_APPROVED    = '1023';
    const STATUS_REJECTED  = '1025';
    const STATUS_PAUSED   = '1026';
    const STATUS_SOLD_OUT   = '1027';
    /**
     * Reference to the attribute instance
     *
     * @var Mage_Catalog_Model_Resource_Eav_Attribute
     */
    protected $_attribute;

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/product_featured');
    }
        /**
     * Retrieve option array
     *
     * @return array
     */
    static public function getOptionArray()
    {
        return array(
            self::STATUS_YES    => Mage::helper('catalog')->__('Yes'),
            self::STATUS_NO   => Mage::helper('catalog')->__('No')
        );
    }
 	
 	static public function getOptionSellerProductStatusArray()
    {
        return array(
            self::STATUS_PPENDING_REVIEW => Mage::helper('catalog')->__('Pending review'),
            self::STATUS_APPROVED   => Mage::helper('catalog')->__('Approved'),
            self::STATUS_REJECTED => Mage::helper('catalog')->__('Rejected'),
            self::STATUS_PAUSED   => Mage::helper('catalog')->__('Paused'),
            self::STATUS_SOLD_OUT   => Mage::helper('catalog')->__('Sold out')
        );
    }
        public function sellerproductstatus()
        {
            $attributeSellerproductstatus = Mage::getModel('eav/config')->getAttribute('catalog_product', 'seller_product_status');
    $attributeSellerproductstatus = $attributeSellerproductstatus->getSource()->getAllOptions(true, true);
    foreach ($attributeSellerproductstatus as $sellerproductstatus_instance) {
        
    	//$sellerproductstatus = '<select name="seller_product_status">';
        if($sellerproductstatus_instance['label']!=''){
            $sellerproductstatus[$sellerproductstatus_instance['value']] = $sellerproductstatus_instance['label'];
         //   $sellerproductstatus .=  '<option value="'.$sellerproductstatus_instance['value'].'">'.$sellerproductstatus_instance['label'].'</option>';
        }
       // $sellerproductstatus = '</select>';
    }
    return $sellerproductstatus;
       }
    /**
     * Retrieve option array with empty value
     *
     * @return array
     */
    static public function getAllOptions()
    {
        $res = array(
            array(
                'value' => '',
                'label' => Mage::helper('catalog')->__('-- Please Select --')
            )
        );
        foreach (self::getOptionSellerProductStatusArray() as $index => $value) {
            $res[] = array(
               'value' => $index,
               'label' => $value
            );
        }
        return $res;
    }
}