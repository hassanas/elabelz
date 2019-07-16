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
class Progos_ProductsUpdater_Adminhtml_ProductsupdaterbackendController extends Mage_Adminhtml_Controller_Action
{

    /*
     * product collections
     * */
    protected $productCollection = null;

    /**
     * @return null
     */
    public function getProductCollection()
    {
        return $this->productCollection;
    }

    /**
     * @param null $productCollection
     */
    public function setProductCollection($primary, $data)
    {
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('manufacturer');
        if ($primary == 'sku')
            $productCollection->addAttributeToFilter('sku', array('in' => $data));
        else {
            $productCollection->addAttributeToFilter('entity_id', array('in' => $data));
        }

        $productCollection->setOrder('entity_id');
        $this->productCollection = $productCollection;
    }


    //protected $allowedStores = Mage::getStoreConfig('sectionName/groupName/fieldName');
    /*
     * For patch SUPEE-6285 mandatory for custom modules
     *
     * */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/productsupdater');
    }

    /*
     * render layout function
     *
     * */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_title($this->__("Product Updator"));
        $this->renderLayout();
    }

    /*
     * Save function which is responsible to update given attributes present in the file
     *
     * */
    public function saveAction()
    {
        if ($this->getRequest()->isPost() && !empty($_FILES['import_attribute_file']['tmp_name'])) {
            if (!$this->_getHelper()->allowedExtension()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('productsupdater')->__('Invalid file type, only csv upload.'));
            } else {
                try {

                    if ($this->_importAttributes())
                        Mage::getSingleton('adminhtml/session')->addSuccess($this->_getHelper()->__('The Attributes Updated.'));

                } catch (Mage_Core_Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($this->_getHelper()->__('Invalid file upload attempt'));
                }
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Function which process the file
     */
    protected function _importAttributes()
    {
        $fileName = $_FILES['import_attribute_file']['tmp_name'];
        $csvObject = new Varien_File_Csv();
        $csvProducts = $csvObject->getData($fileName);
        $header = $csvProducts[0];
        unset($csvProducts[0]);
        if (!$this->_getHelper()->isValidAttributeList($header)) {
            Mage::getSingleton('adminhtml/session')->addError($this->_getHelper()->__('Invalid Attribute code in the file.'));
            return false;
        }
        $englishStoreIdsToUpdate = explode(',', Mage::getStoreConfig('products_updator/products_attribute_updator/english_stores_to_update'));
        $arabicStoreIdsToUpdate = explode(',', Mage::getStoreConfig('products_updator/products_attribute_updator/arabic_stores_to_update'));
        // if sku present as key then in this case replace all skus with corresponding entity_id
        if ($header[0] == 'sku') {
            $productSkus = array();
            $key = 0;
            $productSkus = array_map(function ($item) use ($key) {
                return $item[$key];
            }, $csvProducts);
            $this->setProductCollection('sku', $productSkus);
            foreach($csvProducts as $key=>$csvProduct){
                $updated = 1;
                foreach ($this->getProductCollection() as $product) {
                    if ($product->getSku() != $csvProduct[0]) continue;
                    $csvProducts[$key][0] = (integer)$product->getId();
                    $updated = 2;break;
                }
                // remove those entries having wrong sku in the file
                if($updated==1){
                    unset($csvProducts[$key]);
                }
            }
        } else {
            $productIds = array();
            $key = 0;
            $productIds = array_map(function ($item) use ($key) {
                return $item[$key];
            }, $csvProducts);
            $this->setProductCollection('entity_id', $productIds);
            // remove those entries having wrong ids in the file
            foreach($csvProducts as $key=>$csvProduct){
                $updated = 1;
                foreach ($this->getProductCollection() as $product) {
                    if ($product->getId() != $csvProduct[0]) continue;
                    $updated = 2;break;
                }
                // remove those entries having wrong sku
                if($updated==1){
                    unset($csvProducts[$key]);
                }
            }
        }
        $productActionModel = Mage::getSingleton('catalog/product_action');
        foreach ($csvProducts as $csvProduct) {
            //for admin
            $productActionModel->updateAttributes(array($csvProduct[0]), array($header[1] => $csvProduct[1]), Mage_Core_Model_App::ADMIN_STORE_ID);

            // No need  update in eng store as it will use the same as default
            // Note: I Leave this code intentionally for future use
            /*foreach($englishStoreIdsToUpdate as $id) {
                $productActionModel->updateAttributes(array($csvProduct[0]), array($header[1] => $csvProduct[1]), (integer)$id);
            }*/

            // update in arabic store
            foreach ($arabicStoreIdsToUpdate as $id) {
                $productActionModel->updateAttributes(array($csvProduct[0]), array($header[1] => $csvProduct[2]), (integer)$id);
            }
            // if name or manufacturer changed then in this case url also need to be changed
            if ($header[1] == 'name' or $header[1] == 'manufacturer') {
                foreach ($this->getProductCollection() as $product) {
                    if ($product->getId() != $csvProduct[0]) continue;

                    $seoUrl = "buy " . $product->getAttributeText('manufacturer') . " " . $csvProduct[1] . " ";
                    $cats = $product->getResource()->getCategoryIds($product);
                    sort($cats);
                    // here get the parent category ids which need to exclude from product url
                    // also get all the child categories from above to remove
                    $excludedCategories = explode(',', Mage::getModel('core/variable')->setStoreId(Mage::app()->getStore()->getId())->loadByCode('exclude_categories_from_product_url')->getValue('text'));
                    $toExclude = array();
                    foreach ($excludedCategories as $excludedCategory) {
                        $toExclude = Mage::helper('mirasvitseo')->retrieveAllChildCategories($excludedCategory);
                        $toExclude = Mage::helper('mirasvitseo')->getAllKeysForMultiLevelArrays($toExclude);
                        $cats = array_diff($cats, $toExclude);
                    }
                    $cats = array_diff($cats, $excludedCategories);
                    $targetedCat = array();
                    if (count($cats) > 0) {
                        $targetedCat[] = reset($cats);
                        $targetedCat[] = end($cats);
                        $categories = Mage::getResourceModel('catalog/category_collection')
                            ->addAttributeToSelect('name')
                            ->addAttributeToFilter('entity_id', array('in' => $targetedCat));
                        $seoUrl .= "for ";
                        foreach ($categories as $category)
                            $seoUrl .= $category->getName() . " ";
                    }
                    $seoUrl .= $product->getId() . " ";
                    $seoUrl = preg_replace('#[^0-9a-z]+#i', '-', Mage::helper('catalog/product_url')->format($seoUrl));
                    $seoUrl = strtolower($seoUrl);
                    $seoUrl = trim($seoUrl, '-');
                }

                // save url ad admin store
                $productActionModel->updateAttributes(array($csvProduct[0]), array('url_key' => $seoUrl), Mage_Core_Model_App::ADMIN_STORE_ID);
                // url need to update on all store
                foreach ($englishStoreIdsToUpdate as $id) {
                    $productActionModel->updateAttributes(array($csvProduct[0]), array('url_key' => $seoUrl), (integer)$id);
                }
                foreach ($arabicStoreIdsToUpdate as $id) {
                    $productActionModel->updateAttributes(array($csvProduct[0]), array('url_key' => $seoUrl), (integer)$id);
                }
            }
        }
        return true;
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        return Mage::helper('productsupdater');
    }

}