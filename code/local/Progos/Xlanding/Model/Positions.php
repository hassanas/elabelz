<?php
/**
 * MagiDev
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade MagiDev Package to newer
 * versions in the future. If you wish to customize Package for your
 * needs please refer to http://www.magidev.com for more information.
 *
 * @category    Magidev
 * @package     Magidev_Sort
 * @copyright   Copyright (c) 2014 MagiDev. (http://www.magidev.com)
 */

/**
 * Model of the Package: Change positions of products in category
 *
 * @category   Magidev
 * @package    Magidev_Sort
 * @author     Magidev Team <support@magidev.com>
 */
class Progos_Xlanding_Model_Positions  extends Mage_Core_Model_Abstract
{
    public function updatePosition( $_pageId,$_productID,$_position, $storeId=0 ){
        /**
         * @var Magento_Db_Adapter_Pdo_Mysql $dbWrite
         */
		$resource=Mage::getSingleton('core/resource');
        $dbWrite=$resource->getConnection('core_write');
        $dbWrite->update($resource->getTableName('xlanding/page_product'),array('position'=>$_position),'product_id='.$_productID.' AND page_id='.$_pageId);
//        $dbWrite->update($resource->getTableName('xlanding/page_product'),array('position'=>$_position),'product_id='.$_productID.' AND category_id='.$_categoryId.(($storeId)?' AND store_id='.$storeId:''));
    }

	public function removePosition( $_pageId,$_productID, $storeId=0 ){
		/**
		  * @var Magento_Db_Adapter_Pdo_Mysql $dbWrite
		  */
		$resource=Mage::getSingleton('core/resource');
		$dbWrite=$resource->getConnection('core_write');
		$dbWrite->delete($resource->getTableName('xlanding/page_product'),"product_id={$_productID} AND page_id={$_pageId}");

	}

	public function addNewPosition( $categoryId, $productIds, $positions, $storeId=0 ){
		if(!is_array($productIds)){
			$productIds=array($productIds);
		}
		$insertData=array();
		foreach($productIds as $_key=>$_productId ){
			$insertData[]=array(
				'category_id'=>$categoryId,
				'product_id'=>$_productId,
				'position'=>$positions[$_key]
			);
		}
		/**
		  * @var Magento_Db_Adapter_Pdo_Mysql $dbWrite
		  */
		$resource=Mage::getSingleton('core/resource');
		$dbWrite=$resource->getConnection('core_write');
		$dbWrite->insertMultiple($resource->getTableName('catalog/category_product'),$insertData);
	}
}