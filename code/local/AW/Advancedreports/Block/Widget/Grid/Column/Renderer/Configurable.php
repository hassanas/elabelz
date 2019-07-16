<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Advancedreports
 * @version    2.7.3
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancedreports_Block_Widget_Grid_Column_Renderer_Configurable
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
   public function render(Varien_Object $row)
    {
        $name = $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
        $value = $row->getData($this->getColumn()->getIndex());
        
        //getting item through item id
        $item = Mage::getModel("sales/order_item")->load($value);
        
        //getting configurable product id
        $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
                  ->getParentIdsByChild($item->getProductId());
        $product = Mage::getModel('catalog/product')->load($parentIds[0]);
        
        $url = Mage::helper('adminhtml')->getUrl('adminhtml/awadvancedreports_configurable/index/', array('item_id'=>$value,'product_id'=>$item->getProductId(),'parent_item_id'=>$product->getId()));
        $orderDetails = "<span style='cursor: pointer' onclick='openPopupForm(\"{$url}\",".$value.",".$item->getProductId().",".$product->getId().")' id='$value'>+</span>";
        return $orderDetails;
    }
}