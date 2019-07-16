<?php

class Progos_SaleProductsCategories_Adminhtml_OnsaleController extends Mage_Adminhtml_Controller_Action
{
		protected function _isAllowed()
		{
			return true;
		}
    public function mapAction() {
        if (Mage::getStoreConfig('catalog/salecategory/clearTable')){
            $_resource =Mage::getSingleton('core/resource');
            $write = $_resource->getConnection('core_write');
            $saleProductsCategoriesTable = $_resource->getTableName('sale_products_categories');
            $write->truncate($saleProductsCategoriesTable);
        }
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


		public function indexAction() 
		{
			    $this->_title($this->__("SaleProductsCategories"));
			    $this->_title($this->__("Onsale"));

				$this->_initAction();
				$this->renderLayout();
		}


		public function newAction()
		{

		$this->_title($this->__("SaleProductsCategories"));
		$this->_title($this->__("Onsale"));
		$this->_title($this->__("New Item"));

        $id   = $this->getRequest()->getParam("id");
		$model  = Mage::getModel("saleproductscategories/onsale")->load($id);

		$data = Mage::getSingleton("adminhtml/session")->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		Mage::register("onsale_data", $model);

		$this->loadLayout();
		$this->_setActiveMenu("saleproductscategories/onsale");

		$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Onsale Manager"), Mage::helper("adminhtml")->__("Onsale Manager"));
		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Onsale Description"), Mage::helper("adminhtml")->__("Onsale Description"));


		$this->_addContent($this->getLayout()->createBlock("saleproductscategories/adminhtml_onsale_edit"))->_addLeft($this->getLayout()->createBlock("saleproductscategories/adminhtml_onsale_edit_tabs"));

		$this->renderLayout();

		}




    public function onsaleAction(){
        $this->_title($this->__("SaleProductsCategories"));
        $this->_title($this->__("New Sale"));

        $this->_initAction();
        $this->renderLayout();
    }
    public function expiredAction(){
        $this->_title($this->__("SaleProductsCategories"));
        $this->_title($this->__("Sale Expired"));

        $this->_initAction();
        $this->renderLayout();
    }
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu("saleproductscategories/onsale")->_addBreadcrumb(Mage::helper("adminhtml")->__("Onsale  Manager"),Mage::helper("adminhtml")->__("Onsale Manager"));
        return $this;
    }

    public function exportCsvAction()
    {
        $fileName   = 'onsale.csv';
        $grid       = $this->getLayout()->createBlock('saleproductscategories/adminhtml_onsale_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    public function exportExcelAction()
    {
        $fileName   = 'onsale.xml';
        $grid       = $this->getLayout()->createBlock('saleproductscategories/adminhtml_onsale_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
    public function expiredCsvAction()
    {
        $fileName   = 'expiredSaleCSV.csv';
        $grid       = $this->getLayout()->createBlock('saleproductscategories/adminhtml_expired_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    public function expiredExcelAction()
    {
        $fileName   = 'expiredSaleExcel.xml';
        $grid       = $this->getLayout()->createBlock('saleproductscategories/adminhtml_expired_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

}
