<?php
class Progos_NewArrivals_Adminhtml_NewarrivalsController extends Mage_Adminhtml_Controller_Action
{

	protected function _isAllowed()
	{
		return true;
	}

    public function mapAction() {
        $sourceRoot= Mage::getStoreConfig('catalog/newarrivals/root_category');
        $destinationRoot = Mage::getStoreConfig('catalog/newarrivals/new_arrival_root');
        $newarrivalsModel= Mage::getModel('newarrivals/newarrivals');
        $sourceCategory= Mage::getModel('catalog/category')->load($sourceRoot);
        $childcat= $sourceCategory->getChildrenCategories();
        foreach ($childcat as $key => $value) {
            $newarrivalsModel->mapCategory(
                $value->getId(),
                $destinationRoot
            );
        }
        if (Mage::getStoreConfig('catalog/newarrivals/copymissingcat')) {
            $saleCategoryCollection=$newarrivalsModel->getCollection();
            $categories = $saleCategoryCollection->getColumnValues('category_id');
            foreach ($childcat as $key => $value) {
                $newarrivalsModel->copyMissingCategory(
                    $value,
                    $categories,
                    $destinationRoot
                );
            }
        }
        Mage::getSingleton('core/session')->addSuccess('Categories Mapped');
        $this->_redirectReferer();
    }
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu("newarrivals/newarrivals")->_addBreadcrumb(Mage::helper("adminhtml")->__("Newarrivals  Manager"),Mage::helper("adminhtml")->__("Newarrivals Manager"));
        return $this;
    }


    public function newAction()
    {
        $this->_title($this->__("NewArrivals"));
        $this->_title($this->__(" Newarrivals"));

        $this->_initAction();
        $this->renderLayout();
    }
    public function oldAction()
    {
        $this->_title($this->__("Products New Arrival status Expired"));
        $this->_title($this->__(" Products New Arrival status Expired"));

        $this->_initAction();
        $this->renderLayout();
    }
    public function exportCsvAction()
    {
        $fileName   = 'newarrivals.csv';
        $grid       = $this->getLayout()->createBlock('newarrivals/adminhtml_newarrivals_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    public function exportExcelAction()
    {
        $fileName   = 'newarrivals.xml';
        $grid       = $this->getLayout()->createBlock('newarrivals/adminhtml_newarrivals_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
    public function expiredCsvAction()
    {
        $fileName   = 'expiredNewCSV.csv';
        $grid       = $this->getLayout()->createBlock('newarrivals/adminhtml_expired_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    public function expiredExcelAction()
    {
        $fileName   = 'expiredNewExcel.xml';
        $grid       = $this->getLayout()->createBlock('newarrivals/adminhtml_expired_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }


}