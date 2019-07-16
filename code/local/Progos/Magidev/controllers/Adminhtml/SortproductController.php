<?php
/**
 * @author Hassan Ali <hassan.ali@progos.org>
 */

require_once(Mage::getModuleDir('controllers', 'Magidev_Sort') . DS . 'Adminhtml' . DS . 'SortproductController.php');

class Progos_Magidev_Adminhtml_SortproductController extends Magidev_Sort_Adminhtml_SortproductController
{
    public function loadProductsAction()
    {
        $isAjax = $this->getRequest()->getQuery('isAjax');
        $categoryId = (int)$this->getRequest()->getParam('id');
        if (!Mage::getStoreConfig('progos_merchandising/general/cronmerchandisingstatus')) {
            $productPositions = $this->getRequest()->getParam('product_positions');
        } else {
            $obj = Mage::getModel('progos_merchandising/positions')->load($categoryId, 'category_id');
            if (!empty($obj->getData('position_id')) && $obj->getData('is_active') == '1') {
                $jsonPositions = $obj->getData('positions');
                $productPositions = array();
                foreach (explode('&', $jsonPositions) as $each) {
                    $productPositions[explode('=', $each)[0]] = explode('=', $each)[1];
                }
            } else {
                $productPositions = $this->getRequest()->getParam('product_positions');
            }
        }
        //Fetch gategories Active products to remove from above positions array
        $activeCategoryProducts = Mage::helper('progos_magidev')->getCategoryActiveProducts($categoryId);
        $activeCategoryProductsIds = array();
        foreach ($activeCategoryProducts as $activeCategoryProduct) {
            $activeCategoryProductsIds[] = $activeCategoryProduct->getEntityId();
        }
        foreach ($productPositions as $key => $productPosition) {
            if (in_array($key, $activeCategoryProductsIds)) continue;
            unset($productPositions[$key]);
        }
        $response = null;

        if ($isAjax && $productPositions && $categoryId) {
            $products = $this->_getProducts($categoryId, $productPositions);

            $block = $this->getLayout()->createBlock('magidev_sort/adminhtml_catalog_category_tab_sort');
            $block->setCategoryProducts($products);
            $block->setTemplate('progos/magidev/sort/products.phtml');

            $response = $block->toHtml();
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json')->setBody(Mage::helper('core')->jsonEncode($response));
    }

    protected function _getProducts($_categoryID, $_productPositions)
    {
        $arrProducts = array();
        $_model = Mage::getModel('magidev_sort/positions');

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

        foreach ($_productPositions as $productId => $_position) {

            if (!$_product = $collection->getItemById($productId)) {
                continue;
            }


            if ($_product->getTypeId() == "configurable" && $_product->getStatus() == 1 && $_product->getStockItem()->getIsInStock() == 1) {
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
                    $_product->setIndicator("green");
                    $arrProducts[$_position] = $_product;

                } elseif ($percentage >= 20 AND $percentage < 70) {
                    $_product->setIndicator("orange");
                    $arrProducts[$_position] = $_product;

                } elseif ($percentage >= 70 AND $percentage <= 85) {
                    $_product->setIndicator("red");
                    $arrProducts[$_position] = $_product;

                } elseif ($percentage > 85 AND $percentage <= 99) {
                    $_product->setIndicator("dred");
                    $arrProducts[$_position] = $_product;

                } elseif ($percentage == 100) {
                    $_product->setIndicator("stock");
                    continue;
                    $arrProducts[$_position] = $_product;

                }
            } elseif ($_product->getStatus() == 2) {
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                $_product->setIndicator("disable");
                $arrProducts[$_position] = $_product;

            } elseif ($_position == 0) {
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                $arrProducts[$_position] = $_product;

            } elseif ($_product->getStockItem()->getIsInStock() == 0) {
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                $_product->setIndicator("stock");
                $arrProducts[$_position] = $_product;
            }
        }

        if (Mage::getStoreConfig('catalog/frontend/merchandising_sort_direction') == Progos_Magidev_Block_Sort_Adminhtml_Catalog_Category_Tab_Sort::SORT_DIRECTION_ASC) {
            ksort($arrProducts);
        } else {
            krsort($arrProducts);
        }

        return $arrProducts;
    }

    public function autosortAction()
    {
        $_categoryId = (int)$this->getRequest()->getParam('id');
        $_category = Mage::getModel('catalog/category')->load($_categoryId);

        if (Mage::getSingleton('core/session')->getMagiBackendStoreId()) {
            $_category->setStoreId(Mage::getSingleton('core/session')->getMagiBackendStoreId());
        }
        $_productPositions = $_category->getProductsPosition();

        $activeCategoryProducts = Mage::helper('progos_magidev')->getCategoryActiveProducts($_categoryId);
        $activeCategoryProductsIds = array();
        foreach ($activeCategoryProducts as $activeCategoryProduct) {
            $activeCategoryProductsIds[] = $activeCategoryProduct->getEntityId();
        }
        foreach ($_productPositions as $key => $productPosition) {
            if (in_array($key, $activeCategoryProductsIds)) continue;
            unset($_productPositions[$key]);
        }

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
                    $_arrProductsGreen[] = array($_position, $_product->getEntityId());

                } elseif ($percentage >= 20 AND $percentage < 70) {
                    $_arrProductsOrange[] = array($_position, $_product->getEntityId());

                } elseif ($percentage >= 70 AND $percentage <= 85) {
                    $_arrProductsRed[] = array($_position, $_product->getEntityId());

                } elseif ($percentage > 85 AND $percentage <= 99) {
                    $_arrProductsDRed[] = array($_position, $_product->getEntityId());

                } elseif ($percentage == 100) {
                    $_product->setIndicator("stock");
                    $_arrProductsOutOfStock[] = array($_position, $_product->getEntityId());
                    continue;

                }

            } elseif ($_product->getStatus() == 2) {
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                $_product->setIndicator("disable");
                $_arrProductsDisabled[] = array($_position, $_product->getEntityId());
                continue;

            } elseif ($_position == 0) {
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                $_product->setIndicator("new");
                $_arrProductsNew[] = $_product->getEntityId();

            } elseif ($_product->getStockItem()->getIsInStock() == 0) {
                $_product->setInStockAndTotal($qty . "/" . $ttl);
                $_product->setIndicator("stock");
                $_arrProductsOutOfStock[$_position] = $_product->getEntityId();
                continue;
            }
        }

        $zero =
        $nonZero = [];
        $_index = 0;
        $afterOrange = $this->getRepositionedProducts($_arrProductsGreen,$_arrProductsOrange);
        $afterRed = $this->getRepositionedProducts($afterOrange,$_arrProductsRed);
        $afterDRed = $this->getRepositionedProducts($afterRed,$_arrProductsDRed);
        $maxPos = $this->findMaxIndex($afterDRed);
        if (Mage::getStoreConfig('catalog/frontend/merchandising_show_new_first_frontend') == 1) {
            foreach ($_arrProductsNew as $each) {
                $zero[] = $each . "=" . '0';
            }
        } else {
            foreach ($_arrProductsNew as $_product) {
                $_index++;
                $_position = $maxPos + $_index;
                $zero[] = $_product . "=" . $_position;
            }
        }
        foreach ($afterDRed as $each) {
            $nonZero[] = $each[1] . "=" . $each[0];
        }

        $resultant = array_merge($zero, $nonZero);

        $resultant = implode("&", $resultant);
        $this->saveCategoryCustom($_categoryId,$resultant);
        echo sizeof($_productPositions) - sizeof($_arrProductsOutOfStock);
    }

    public function getRepositionedProducts($positioned, $yetToPosition){
        $maxPos = $this->findMaxIndex($positioned);
        $_positionedAndMerged = [];
        $_index = 0;
        foreach ($yetToPosition as $_product) {
            $_index++;
            $_position = $maxPos + $_index;
            $_positionedAndMerged[] = array($_position, $_product[1]);
        }
        return array_merge($positioned ,$_positionedAndMerged);
    }

    public function findMaxIndex($afterOrange)
    {
        $max = max(array_column($afterOrange, array_shift(array_keys($afterOrange))));
        return $max;
    }

    public function saveCategoryCustom($categoryId, $ProPos) {
        $categoryProducts = $ProPos;
        if (Mage::getStoreConfig('progos_merchandising/general/cronmerchandisingstatus')) {
            $obj = Mage::getModel('progos_merchandising/positions')->load($categoryId, 'category_id');
            if (!empty($obj->getData('position_id'))) {
                $obj->setPositions($categoryProducts);
                $obj->setIsActive('1');
                $obj->setMerchandisedAt(Mage::getModel('core/date')->timestamp());
                try {
                    $obj->save();
                } catch (Exception $e) {
                    Mage::log($e->getMessage(), null, 'merchandising.log', true);
                }
            } else {
                $_model = Mage::getModel('progos_merchandising/positions');
                $_model->setCategoryId($categoryId);
                $_model->setPositions($categoryProducts);
                $_model->setMerchandisedAt(Mage::getModel('core/date')->timestamp());
                try {
                    $_model->save();
                } catch (Exception $e) {
                    Mage::log($e->getMessage(), null, 'merchandising.log', true);
                }
            }
        }

    }

    public function mannualpublishAction()
    {
        $categoryId = $this->getRequest()->getParam('id');
        $obj = Mage::getModel('progos_merchandising/positions')->load($categoryId, 'category_id');
        $result = array();
        if (!empty($obj->getData('position_id'))) {
            Mage::getModel('progos_merchandising/cron')->savePositions($obj->getData(), '1');
            $result['status'] = true;
        } else {
            $result['status'] = false;
        }
        echo json_encode($result);

    }


    protected function _isAllowed()
    {
        return true;
    }


}