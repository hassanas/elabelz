<?php
class Progos_Xlanding_Adminhtml_XlandingController extends Mage_Adminhtml_Controller_Action
{

	protected function _isAllowed()
	{
		return true;
	}


    public function autosortAction() {
        $_categoryId = (int) $this->getRequest()->getParam('id');
        $_category = Mage::getModel('catalog/category')->load($_categoryId);
        $pageId  = (int) $this->getRequest()->getParam('id');
        $page    = Mage::getModel('amlanding/page');
        if ($pageId) {
            $page->load($pageId);
        }

        if (Mage::getSingleton('core/session')->getMagiBackendStoreId()) {
            $_category->setStoreId(Mage::getSingleton('core/session')->getMagiBackendStoreId());
        }

        $_resource =Mage::getSingleton('core/resource');
        $read = $_resource->getConnection('core_read');
        $table = $_resource->getTableName('am_landing_page_products');
        $query = 'SELECT product_id, `position` FROM ' . $table .' where page_id='.$pageId;
        $pageProducts = $read->fetchAll($query);
        $productIds = array_column($pageProducts,'product_id');
        $positions = array_column($pageProducts,'position');
        $_productPositions = array_combine($productIds,$positions);

        $activeCategoryProducts = Mage::helper('xlanding')->getPageActiveProducts($pageId);
        $activeCategoryProductsIds = array();
        foreach ($activeCategoryProducts as $activeCategoryProduct) {
            $activeCategoryProductsIds[] = $activeCategoryProduct->getEntityId();
        }
        foreach ($_productPositions as $key => $productPosition) {
            if (in_array($key, $activeCategoryProductsIds)) continue;
            unset($_productPositions[$key]);
        }

        $_model = Mage::getModel('xlanding/positions');

        $productModel = Mage::getModel('catalog/product');
        $productModel->setStoreId(Mage::getSingleton('core/session')->getMagiBackendStoreId());

        /** @var  Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = $productModel->getCollection();
        $collection
            ->addFieldToFilter('entity_id', array('in' => array_keys($_productPositions)))
            ->addFieldToFilter('visibility', array('in' => array(2, 4)))
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('status')
            ->addAttributeToSelect('thumbnail')
            ->addAttributeToSelect('status', 1);

        Mage::getSingleton('cataloginventory/stock')
            ->addItemsToProducts($collection);

        $_index = 1;
        $_arrProductsGreen =
        $_arrProductsOrange =
        $_arrProductsDisabled =
        $_arrProductsRed =
        $_arrProductsDRed =
        $_arrProductsNew =
        $_arrProductsOutOfStock = [];

        foreach ($_productPositions as $productId => $_position) {

            if (!$_product = $collection->getItemById($productId)) {
                continue;
            }

            if ($_product->getTypeId() == "configurable" && $_product->getStatus() == 1 && $_position != 0 && $_product->getStockItem()->getIsInStock() == 1) {

                $children = Mage::getSingleton('catalog/product_type_configurable')->getUsedProducts(null, $_product);
                $qty = 0;
                $ttl = 0;

                foreach ($children as $child) {
                    if ((int)$child->getStockItem()->getQty() > 0 && $child->getStockItem()->getIsInStock() == 1) {
                        $qty++;
                    }
                    $ttl++;
                }
                $percentage = 100 - ceil(($qty / $ttl) * 100);
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                if ($percentage < 20) {
                    $_arrProductsGreen[$_position] = $_product;

                } elseif ($percentage >= 20 AND $percentage < 70) {
                    $_arrProductsOrange[$_position] = $_product;

                } elseif ($percentage >= 70 AND $percentage <= 85) {
                    $_arrProductsRed[$_position] = $_product;

                } elseif ($percentage > 85 AND $percentage <= 99) {
                    $_arrProductsDRed[$_position] = $_product;

                } elseif ($percentage == 100) {
                    $_product->setIndicator("stock");
                    $_arrProductsOutOfStock[$_position] = $_product;
                    continue;

                }
            } elseif ($_product->getStatus() == 2) {
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                $_product->setIndicator("disable");
                $_arrProductsDisabled[$_position] = $_product;
                continue;

            } elseif ($_position == 0) {
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                $_product->setIndicator("new");
                $_arrProductsNew[$_position] = $_product;

            } elseif ($_product->getStockItem()->getIsInStock() == 0) {
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                $_product->setIndicator("stock");
                $_arrProductsOutOfStock[$_position] = $_product;
                continue;
            }
        }

        $_arrProductsOrangeFinal =
        $_arrProductsRedFinal =
        $_arrProductsDRedFinal =
        $_arrProductsDisabledFinal =
        $_arrProductsNewFinal =
        $_arrProductsOutOfStockFinal = [];

        if (Mage::getStoreConfig('catalog/frontend/merchandising_show_new_first_frontend') == 1) {
            $afterOrange = $_arrProductsNew + $_arrProductsGreen + $_arrProductsOrange;
        } else {
            $afterOrange = $_arrProductsGreen + $_arrProductsOrange;
        }


        foreach ($_arrProductsRed as $_position => $_product) {
            if (!empty($_arrProductsRed[$_position]) || $_position == 1) {
                $_index++;
                $_position = max(array_keys($afterOrange)) + $_index;
                $_model->updatePosition($_categoryId, $_product->getId(), $_position);
            }
            $_arrProductsRedFinal[$_position] = $_product;
        }

        $afterRed = $afterOrange + $_arrProductsRedFinal;

        foreach ($_arrProductsDRed as $_position => $_product) {
            if (!empty($_arrProductsDRed[$_position]) || $_position == 1) {
                $_index++;
                $_position = max(array_keys($afterRed)) + $_index;
                $_model->updatePosition($_categoryId, $_product->getId(), $_position);
            }
            $_arrProductsDRedFinal[$_position] = $_product;
        }
        $afterDRed = $afterRed + $_arrProductsDRedFinal;

        foreach ($_arrProductsNew as $_position => $_product) {
            if (!empty($_arrProductsNew[$_position]) || $_position == 1) {
                $_index++;
                $_position = max(array_keys($afterDRed)) + $_index;
                $_product->setIndicator("new");
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                if (Mage::getStoreConfig('catalog/frontend/merchandising_show_new_first_frontend') == 1) {
                    $_model->updatePosition($_categoryId, $_product->getId(), 0);
                } else {
                    $_model->updatePosition($_categoryId, $_product->getId(), $_position);
                }
            }
            $_arrProductsNewFinal[$_position] = $_product;
        }
        Mage::getSingleton('core/session')->addSuccess(Mage::helper('xlanding')->__('%s Products sorted.',sizeof($_productPositions) - sizeof($_arrProductsOutOfStock)));
    $this->_redirectReferer();
	}
    
    public function deleteAction() {
        if( $this->getRequest()->isAjax()&&$this->getRequest()->getParam('id') ){
            try{
                Mage::getModel('xlanding/positions')->removePosition($this->getRequest()->getParam('pageId'),$this->getRequest()->getParam('id'));
            } catch( Exception $e ){
                $this->getResponse()->getBody($e->getMessage());
                return;
            }
            $this->getResponse()->setBody(1);
        }
    }

    public function saveAction()
    {
        $_data=$this->getRequest()->getPost();
        $_model=Mage::getModel('xlanding/positions');
        if(!isset($_data['product1'])){
            $pageId=$_data['categoryId'];
            unset($_data['categoryId']);
            unset($_data['form_key']);
            foreach( $_data as $_id=>$position ){
                $_model->updatePosition($pageId,$_id,$position,(int)$this->getRequest()->getParam('store', 0));
            }
        } else {
           try {
               $_model->updatePosition($_data['categoryId'],$_data['product1'],$_data['position1'],(int)$this->getRequest()->getParam('store', 0));
               $_model->updatePosition($_data['categoryId'],$_data['product2'],$_data['position2'],(int)$this->getRequest()->getParam('store', 0));
               echo "Updated";
           } catch (Exception $e) {
               echo $e->getMessage();
           }

        }
    }

    public function searchAction(){
        $_collection=Mage::getModel('xlanding/search')
            ->setPageId($this->getRequest()->getParam('category_id'))
            ->setQuery($this->getRequest()->getParam('query'))->getCollection($this->getRequest()->getParam('store'));
        $_array=$_collection->toArray();
        $this->getResponse()->setHeader('Content-type','application/json');
        $this->getResponse()->setBody(Zend_Json::encode($_array['items']));
    }

    public function merchandisingAction() {
        $this->_initPage();
        $this->loadLayout();
        $this->getLayout()->getBlock('page.edit.tab.merchandising')
            ->setPageId((int) $this->getRequest()->getParam('id',null));
        $this->renderLayout();
    }
    protected function _initPage()
    {
        $pageId  = (int) $this->getRequest()->getParam('id');
        $page    = Mage::getModel('amlanding/page');
        if ($pageId) {
            $page->load($pageId);
        }
        Mage::register('current_page', $page);
        return $page;
    }
}