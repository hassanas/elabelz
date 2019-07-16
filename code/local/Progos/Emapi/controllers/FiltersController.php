<?php

class Progos_Emapi_FiltersController extends Mage_Core_Controller_Front_Action
{
    public function getAttribute($attributeCode)
    {
        $attributeId = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setCodeFilter($attributeCode)->getFirstItem()->getAttributeId();

        $attributeOptions = Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setAttributeFilter($attributeId)
            ->setStoreFilter(0)
            ->setPositionOrder()
            ->load()
            ->toOptionArray();

        return $attributeOptions;

        $attrs = array();
        foreach ($attributeOptions AS $attributeOption) {
            $attrs[] = array('code' => $attributeOption['value'], 'label' => $attributeOption['label']);
        }

        header("Content-Type: application/json");
        print_r(json_encode($attrs));
        die;
    }

    /**
     * This function used for app filters
     *
     */
    public function layerednavAction()
    {
        if (isset($_GET["s"]) && !empty($this->getRequest()->getParam('s'))) {
            $data = Mage::getModel('emapi/filters')->klevuSearchFilters($this);
            header("Content-Type: application/json");
            echo json_encode($data);
            exit;
        }

        $fpcModel = Mage::getModel('fpccache/fpc');
        $fpcModel->setControllerObject($this);
        $cacheData = $fpcModel->getData();
        if (!empty($cacheData)) {
            header("Content-Type: application/json");
            echo $cacheData;
            die;
        }

        $attrs = Mage::getModel('emapi/filters')->oldFilters($this);
        $fpcModel->setData($attrs);
        header("Content-Type: application/json");
        print_r(json_encode($attrs));
        die;
    }

    /**
     *  This function will return complete filters for Mobile API call
     *
     */
    public function getCatalogFiltersAction()
    {
        $attrs = Mage::getModel('emapi/filters')->filterCombinations($this);

        if (Mage::getStoreConfig('api/emapi/filterscompression')) {
            $attrs = gzcompress($attrs, 9);
            header("Content-Type: gzip");
            echo $attrs;
            die;
        }
        else{
            header("Content-Type: application/json");
            echo $attrs;
            die;
        }

    }

    /**
     *  This function will return complete search filters for Mobile API call
     *
     */
    public function getCatalogSearchFiltersAction()
    {
        $data = Mage::getModel('emapi/filters')->klevuSearchFilters($this);
        header("Content-Type: application/json");
        echo json_encode($data);
        exit;
    }


    protected function array_orderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    public function _getHelper($helper = null)
    {
        return ($helper == null) ? Mage::helper('emapi') : Mage::helper($helper);
    }

}