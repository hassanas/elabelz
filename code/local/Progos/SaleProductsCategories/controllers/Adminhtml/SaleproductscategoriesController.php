<?php
class Progos_SaleProductsCategories_Adminhtml_SaleproductscategoriesController extends Mage_Adminhtml_Controller_Action
{
	
	protected function _isAllowed()
	{
		//return Mage::getSingleton('admin/session')->isAllowed('saleproductscategories/saleproductscategories');
		return true;
	}

	
    public function mapAction() {
    	$sourceRoot= Mage::getStoreConfig('catalog/salecategory/sourcerootcategory');
        $destinationRoot = Mage::getStoreConfig('catalog/salecategory/salerootcategory');
    	$saleCategoryModel= Mage::getModel('saleproductscategories/salecategories');
    	$sourceCategory= Mage::getModel('catalog/category')->load($sourceRoot);
    	$childcat= $sourceCategory->getChildrenCategories();
        foreach ($childcat as $key => $value) {
                 $saleCategoryModel->mapCategory(
                     $value->getId(),
                     $destinationRoot
                 );
            }
        if (Mage::getStoreConfig('catalog/salecategory/copymissingcat')) {
            $saleCategoryCollection=$saleCategoryModel->getCollection();
            $categories = $saleCategoryCollection->getColumnValues('category_id');
            foreach ($childcat as $key => $value) {
                $saleCategoryModel->copyMissingCategory(
                    $value,
                    $categories,
                    $destinationRoot
                );
            }
        }
        Mage::getSingleton('core/session')->addSuccess('Categories Mapped');
        $this->_redirectReferer();
    }

    
}