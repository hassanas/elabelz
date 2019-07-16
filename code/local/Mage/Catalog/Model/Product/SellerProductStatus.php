<?php 
/*
 * @category    Local
 * @package     local_Catalog
 * @author      Azhar Farooq <az.fq.jh@gmail.com>
 */
class Mage_Catalog_Model_Product_sellerProductStatus extends Mage_Core_Model_Abstract
{

    const STATUS_UNAPPROVED = 'Unapproved';
    const STATUS_REJECTED   = 'Rejected';
    const STATUS_INCOMPLETE = 'Incomplete';
    const STATUS_APPROVED   = 'Approved';

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
        $this->_init('catalog/product_sellerProductStatus');
    }
        /**
     * Retrieve option array
     *
     * @return array
     */
    static public function getOptionArray()
    {
        return array(
            self::STATUS_UNAPPROVED => Mage::helper('catalog')->__('Unapproved'),
            self::STATUS_REJECTED   => Mage::helper('catalog')->__('Rejected'),
            self::STATUS_INCOMPLETE => Mage::helper('catalog')->__('Incomplete'),
            self::STATUS_APPROVED   => Mage::helper('catalog')->__('Approved')
        );
    }

}