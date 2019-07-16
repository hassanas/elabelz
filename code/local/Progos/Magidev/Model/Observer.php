<?php
class Progos_Magidev_Model_Observer {
    public function saveCategoryCustom($observer) {
        $categoryProducts = "";
        if (Mage::getStoreConfig('progos_merchandising/general/cronmerchandisingstatus')) {
            if ($observer->getRequest()->getPost()['category_products']) {
                $_data = $observer->getRequest()->getPost();
                $categoryId = $_data['general']['id'];
                $categoryProducts = $_data['category_products'];
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
    }

}