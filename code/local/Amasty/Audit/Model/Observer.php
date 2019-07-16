<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */
class Amasty_Audit_Model_Observer
{
    protected $_logData = array();
    protected $_isOrigData = false;
    protected $_isCustomer = false;
    protected $_isFirstLogout = true;
    protected $_oldRules = array();
    protected $_productLinkData = array(
        'up_sell_link_data',
        'cross_sell_link_data',
        'related_link_data'
    );

    protected $_isAmpgrid = NULL;

    //listen controller_action_predispatch event
    public function saveSomeEvent($observer)
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $username = Mage::getSingleton('admin/session')->getUser() ? Mage::getSingleton('admin/session')->getUser()->getUsername() : '';
            $user = Mage::getModel('admin/user')->loadByUsername($username);
            if ($user && Mage::helper('amaudit')->isUserInLog($user->getId())) {//settings log or not user
                $path = $observer->getEvent()->getControllerAction()->getRequest()->getOriginalPathInfo();
                Mage::register('amaudit_log_path', $path, true);
                $arrPath = ($path) ? explode("/", $path) : array();
                $exportType = NULL;
                if ((in_array('exportCsv', $arrPath)) || in_array('exportXml', $arrPath) || in_array('exportPost', $arrPath)) {
                    $this->_saveExport($arrPath, $observer);
                }
                Mage::register('amaudit_admin_path', $arrPath[1], true);
                $this->_saveCompilation($path, $username);
                $this->_saveCache($path, $username);
                $this->_saveIndex($path, $username);
            }
        }
    }

    public function afterBlockCreate($observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Customer_Edit_Tabs) {
            if (Mage::getSingleton('admin/session')->isAllowed('system/amauditmenu/customerhistory')) {
                $this->_addBlock($block, 'adminhtml_tabs_customer', 'tags');
            }
        }

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View_Tabs) {
            if (Mage::getSingleton('admin/session')->isAllowed('system/amauditmenu/orderhistory')) {
                $this->_addBlock($block, 'adminhtml_tabs_order', 'order_transactions');
            }
        }

        if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs) {
            if ($block->getProduct()->getEntityId()) {
                if (Mage::getSingleton('admin/session')->isAllowed('system/amauditmenu/producthistory')) {
                    $this->_addBlock($block, 'adminhtml_tabs_product', 'super');
                }
            }
        }
    }

    //listen model_delete_after event
    public function modelDeleteAfter($observer)
    {
        if (!Mage::app()->getStore()->isAdmin()) {
            return false;
        }

        $object = $observer->getObject();
        if ($object instanceof Mage_Catalog_Model_Product) {
            $this->_deleteProduct($object);
        } else {
            if (!Mage::registry('amaudit_log_duplicate_save')) {
                $this->_saveLog();
                if (!Mage::registry('amaudit_log_duplicate_save')) {
                    Mage::register('amaudit_log_duplicate_save', 1);
                }
            }
            $this->modelSaveAfter($observer, "Delete");
        }

    }

    //listen model_save_after event
    public function modelSaveAfter($observer, $delete = null)
    {
        $object = $observer->getObject();
        $class = get_class($object);
        /** @var Amasty_Audit_Helper_Data $helper */
        $helper = Mage::helper('amaudit');

        //product grid compatibility
        if (Mage::app()->getStore()->isAdmin()
            && is_a($object, 'Mage_CatalogInventory_Model_Stock_Item')
            && $this->_isAmpgrid()
        ) {
            $this->_saveLog();
            if (!Mage::registry('amaudit_log_duplicate_save')) {
                Mage::register('amaudit_log_duplicate_save', 1);
            }
        }

        if (!$this->_isForSave($observer)
            || !Mage::registry('amaudit_log_id')
        ) {
            return;
        }

        $elementId = $observer->getObject()->getEntityId();

        //product grid compatibility
        if (is_a($object, 'Mage_CatalogInventory_Model_Stock_Item')) {
            $elementId = $observer->getObject()->getProductId();
        }

        if (!$elementId) {
            $elementId = $observer->getObject()->getId();
        }

        if (is_array($elementId) && 'true' == (string)Mage::getConfig()->getNode('modules/Amasty_Perm/active')) {
            $elementId = $observer->getObject()->getUserId();
        }

        $name = "";

        if (strpos($class, 'Sales_Model_Order') !== false) {
            $object = $observer->getObject();
            if ($observer->getObject()->getOrderId()) {
                $name = "Order ID " . $object->getOrderId();
            } elseif ($object->getParentId() == $object->getEntityId()) {
                $name = "Order ID " . $object->getParentId();
            }
        }

        if (!$name) {
            $name = $observer->getObject()->getName();
        }
        if (!$name) {
            $name = $observer->getObject()->getTitle();
        }
        //Catalog->search terms
        if (!$name && $observer->getObject() instanceof Mage_CatalogSearch_Model_Query) {
            $name = $observer->getObject()->getQueryText();
        }
        //Attribute Set
        if (!$name && $observer->getObject() instanceof Mage_Eav_Model_Entity_Attribute_Set) {
            $name = $observer->getObject()->getAttributeSetName();
        }
        //Catalog Ratings
        if (!$name && $observer->getObject() instanceof Mage_Rating_Model_Rating) {
            $name = $observer->getObject()->getRatingCode();
        }
        //Customer Group
        if (!$name && $observer->getObject() instanceof Mage_Customer_Model_Group) {
            $name = $observer->getObject()->getCustomerGroupCode();
        }
        //product grid compatibility
        if (!$name && $observer->getObject() instanceof Mage_CatalogInventory_Model_Stock_Item) {
            $name = $observer->getObject()->getProductName();
        }
        if (!Mage::registry('amaudit_log_duplicate') || $name) {
            Mage::unregister('amaudit_log_duplicate');
            Mage::register('amaudit_log_duplicate', 1);
            try {
                $logModel = Mage::getModel('amaudit/log')->load(Mage::registry('amaudit_log_id'));
                $this->_logData = $logModel->getData();
                if ($logModel) {
                    if ($name && !$this->_isCustomer) $this->_logData['info'] = $name;
                    if ($elementId
                        && !$this->_isCustomer
                        && $class != 'Unirgy_Dropship_Model_Stock_Item'
                    ) {
                        $this->_logData['element_id'] = $elementId;
                    }
                    if ($observer->getObject()->hasDataChanges()) $this->_logData['type'] = "Edit";
                    if ($observer->getObject()->isObjectNew() || ($observer->getObject()->hasDataChanges() && !$observer->getObject()->getOrigData())) {
                        $this->_logData['type'] = "New";
                    }
                    if ($observer->getObject()->isDeleted() || $delete) {
                        $this->_logData['type'] = "Delete";
                    }
                    if ($logModel->getCategoryName() == "System Configuration") {
                        $this->_logData['type'] = "Edit";
                    }
                    if ($logModel->getCategory() == "amaudit/adminhtml_log") {
                        $this->_logData['type'] = "Restore";
                    }

                    //product grid compatibility
                    if ($this->_logData['category'] == 'ampgrid/adminhtml_field') {
                        $this->_logData['category'] = 'admin/catalog_product';
                        $this->_logData['category_name'] = $helper->__('Product');
                        $this->_logData['parametr_name'] = 'id';
                        $this->_isAmpgrid = true;
                    }
                    $logModel->setData($this->_logData);
                    if (!$this->_checkOrder($logModel, $object)) {
                        $logModel->save();
                    }
                    if ($observer->getObject() instanceof Mage_Customer_Model_Customer) {
                        $this->_isCustomer = true;
                    }
                }
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::log($e->getMessage());
            }
        }

        //save details
        if (!empty($class)
            && !empty($elementId)
            && !is_array($elementId)
        ) {
            $entity = Mage::getModel($class)->load($elementId);
            $this->_saveIfNoDetails($entity);
            $logModel = Mage::getModel('amaudit/log')->load(Mage::registry('amaudit_log_id'));
            $logModelType = $logModel->getType() ? $logModel->getType() : 'New';
            $isNew = ($logModel && $logModelType == "New") ? true : false;
            if (is_a($object, 'Mage_Sales_Model_Order_Invoice_Item')) {
                $isNew = true;
            }
            $this->_isOrigData = false;
            if ($observer->getObject()->getOrigData()) {
                $this->_isOrigData = true;
                $dataBeforeSave = Mage::registry('amaudit_details_before');
                Mage::unregister('amaudit_details_before');
                $newData = $object->getData();
                $oldData = $object->getOrigData();

                $amPgridData = Mage::registry('amasty_grid_product_data');
                if (!empty($amPgridData)) {
                    $oldData = $amPgridData;
                    Mage::unregister('amasty_grid_product_data');
                }

                if (is_a($object, 'Mage_Catalog_Model_Product')) {
                    if (!($object->getDescription())) {
                        $newData = Mage::getModel('catalog/product')
                            ->setStoreId($object->getStoreId())
                            ->load($object->getEntityId())
                            ->getData();
                    }

                    $categoryIds = $entity->getCategoryIds();
                    $newData['categories'] = $categoryIds;
                    $oldData['categories'] = $dataBeforeSave['categories'];
                }

                if (is_a($object, 'Mage_Catalog_Model_Category')) {
                    $oldData['posted_products'] = implode(',', array_keys($newData['products_position']));
                    $newData['posted_products'] = implode(',', array_keys($newData['posted_products']));
                }
                $this->_saveDetails($oldData, $newData, Mage::registry('amaudit_log_id'), $isNew, $object);
            }
            if ($entity && !$this->_isOrigData) {
                $newMass = $entity->getData();
                if (array_key_exists('config_id', $newMass) && array_key_exists('path', $newMass) && array_key_exists('value', $newMass)) {
                    $newMass = array($newMass['path'] => $newMass['value']);
                }
                $mass = Mage::registry('amaudit_details_before');
                Mage::unregister('amaudit_details_before');
                $this->_saveDetails($mass, $newMass, Mage::registry('amaudit_log_id'), $isNew, $object);
            }
            //for order comment
            if ((is_a($object, 'Mage_Sales_Model_Order_Status_History')) && (!$observer->getObject()->getOrigData())) {
                $this->_saveDetails(array('comment' => ''), array('comment' => $observer->getObject()->getComment()), Mage::registry('amaudit_log_id'), $isNew, $object);
            }
        }
        Mage::unregister('amaudit_details_before');
    }

    public function beforeSaveRoles()
    {
        $roleId = Mage::app()->getRequest()->getParam('role_id');
        $rulesCollection = $rules_set = Mage::getResourceModel('admin/rules_collection')->getByRoles($roleId)->load();
        $this->_oldRules = $this->_rulesToOptionArray($rulesCollection);
    }

    public function afterSaveRoles()
    {
        $roleId = Mage::app()->getRequest()->getParam('role_id');
        $rulesCollection = $rules_set = Mage::getResourceModel('admin/rules_collection')->getByRoles($roleId)->load();
        $newRules = $this->_rulesToOptionArray($rulesCollection);
        $this->_saveDetails($this->_oldRules, $newRules, Mage::registry('amaudit_log_id'), false, 'admin_rule');
    }

    /**
     * Handles mass change of status in Manage Products
     * @param $observer
     */
    public function modelProductsSaveBefore($observer)
    {
        /** @var Amasty_Audit_Helper_Data $helper */
        $helper = Mage::helper('amaudit');
        $class = 'Mage_Catalog_Model_Product';
        $username = Mage::getSingleton('admin/session')->getUser() ? Mage::getSingleton('admin/session')->getUser()->getUsername() : '';
        $productIds = $observer->getProductIds();
        $observerData = $observer->getAttributesData();
        $isProductMassUpdate = $this->_isProductMassUpdate();

        foreach ($productIds as $productId) {
            if ($isProductMassUpdate) {
                $product = Mage::getModel($class)->load($productId);

                $logModel = Mage::getModel('amaudit/log')->load(Mage::registry('amaudit_log_id'));
                $this->_logData = $logModel->getData();
                $name = $product->getName();
                if ($logModel) {
                    if ($name) $this->_logData['info'] = $name;
                    $this->_logData['element_id'] = $productId;
                    $this->_logData['type'] = "Edit";
                    $this->_logData['category'] = "admin/catalog_product";
                    $this->_logData['category_name'] = $helper->__('Product');
                    $this->_logData['parametr_name'] = "Edit";
                    $this->_logData['store_id'] = $observer->getStoreId();
                    $this->_logData['username'] = $username;
                    $this->_logData['date_time'] = Mage::getModel('core/date')->gmtDate();
                    $logModel->setData($this->_logData);
                    $logModel->save();
                }

                $this->_saveDetails($product->getData(), $observerData, $logModel->getEntityId(), false, $product);
            }
        }

        return $this;
    }


    //listen model_save_before event
    public function modelSaveDeleteBefore($observer)
    {
        $object = $observer->getObject();

        $class = get_class($object);

        if (!$this->_isForSave($observer)
        ) {
            return;
        }

        if (!Mage::registry('amaudit_log_duplicate_save')) {
            $this->_saveLog();
            Mage::register('amaudit_log_duplicate_save', 1, true);
        }

        if (is_a($object, 'Mage_Tax_Model_Class')) {
            $origData = Mage::getModel('tax/class')->load($observer->getObject()->getClassId())->getData();
            if (is_array($origData) && !empty($origData)) {
                foreach ($origData as $key => $value) {
                    $observer->getObject()->setOrigData($key, $value);
                }
            }
        }

        if (is_a($object, 'Mage_Tax_Model_Calculation_Rule')) {
            $origData = Mage::getModel('tax/calculation_rule')->load($observer->getObject()->getTaxCalculationRuleId())->getData();
            if (is_array($origData) && !empty($origData)) {
                foreach ($origData as $key => $value) {
                    $observer->getObject()->setOrigData($key, $value);
                }
            }
        }
        $mass = Mage::registry('amaudit_details_before') ? Mage::registry('amaudit_details_before') : array();
        $id = $observer->getObject()->getId();
        $entity = Mage::getModel($class)->load($id);
        $this->_saveIfNoDetails($entity, $observer);
        if ($entity) {
            $massNew = $entity->getData();
            foreach ($massNew as $mas) {
                if (!(gettype($mas) == "string" || gettype($mas) == "boolean" || is_array($mas))) {
                    unset($mas);
                }
            }

            if (array_key_exists('config_id', $massNew) && array_key_exists('path', $massNew) && array_key_exists('value', $massNew)) {
                $mass[$massNew['path']] = $massNew['value'];
            } else {
                $mass += $massNew;
            }

            if (is_a($observer->getObject(), 'Mage_Catalog_Model_Product')) {
                $categoryIds = $entity->getCategoryIds();
                $mass['categories'] = $categoryIds;
                $this->_saveProductLinkData($observer);
                $this->_saveProductWebsites($observer);
            }

            Mage::register('amaudit_details_before', $mass, true);
        }

    }

    //run with cron
    public function deleteLogs()
    {
        Mage::getModel('amaudit/log')->clearLog();
        Mage::getModel('amaudit/visit')->deletePageVisitHistoryLog();
        Mage::getModel('amaudit/data')->deleteLoginAttemptsLog();
    }

    public function implode_r($glue, $arr)
    {
        $ret_str = "";
        foreach ($arr as $a) {
            $ret_str .= (is_array($a)) ? $this->implode_r($glue, $a) : "," . $a;
        }

        return $ret_str;
    }

    protected function _saveProductWebsites($observer)
    {
        $websiteIds = Mage::getModel('catalog/product')->load($observer->getObject()->getEntityId())->getWebsiteIds();
        Mage::register('amaudit_old_product_websites_ids', $websiteIds, true);
    }

    protected function _saveProductLinkData($observer)
    {
        $object = $observer->getObject();
        foreach ($this->_productLinkData as $linkData) {
            switch ($linkData) {
                case 'up_sell_link_data':
                    $data = $object->getUpSellProductIds();
                    break;
                case 'cross_sell_link_data':
                    $data = $object->getCrossSellProductIds();
                    break;
                case 'related_link_data':
                    $data = $object->getRelatedProductIds();
                    break;
                default:
                    $data = array();
            }
            Mage::register('amaudit_' . $linkData, $data, true);
        }
    }

    protected function _isForSave($observer)
    {
        $isForSave = true;

        $object = $observer->getObject();

        $class = get_class($object);

        if (!Mage::app()->getStore()->isAdmin() ||
            $class == "Amasty_Audit_Model_Log" ||
            $class == "Amasty_Audit_Model_Log_Details" ||
            $class == "Amasty_Audit_Model_Active" ||
            $class == "Amasty_Audit_Model_Visit_Detail" ||
            $class == "Amasty_Audit_Model_Visit" ||
            $class == "Amasty_Editlock_Model_Lock" ||
            is_a($object, 'Mage_Index_Model_Event') ||
            $class == 'Mirasvit_SearchIndex_Model_Index'||
            $class == 'AW_Customerattributes_Model_Value'
        ) {
            $isForSave = false;
        }

        return $isForSave;
    }

    protected function _addBlock($block, $createdBlock, $lastElement)
    {
        if (method_exists($block, 'addTabAfter')) {
            $url = $this->_prepareUrl($createdBlock);
            $block->addTabAfter('tabid', array(
                'label' => Mage::helper('amaudit')->__('History of Changes'),
                'class' => 'ajax',
                'url' => Mage::helper("adminhtml")->getUrl($url, array('_current' => true)),
            ), $lastElement);
        } else {
            $block->addTab('tabid', array(
                'label' => Mage::helper('amaudit')->__('History of Changes'),
                'content' => $block->getLayout()
                    ->createBlock('amaudit/' . $createdBlock)->toHtml(),
            ));
        }

    }

    protected function _prepareUrl($cratedBlock)
    {
        $baseUrl = 'adminhtml/amaudit_log/';
        switch ($cratedBlock) {
            case 'adminhtml_tabs_customer':
                $url = $baseUrl . 'customer';
                break;
            case 'adminhtml_tabs_product':
                $url = $baseUrl . 'product';
                break;
            case 'adminhtml_tabs_order':
                $url = $baseUrl . 'order';
                break;
            default:
                $url = '';
        }

        return $url;
    }

    protected function _saveExport($arrPath, $observer)
    {
        /** @var Amasty_Audit_Helper_Data $helper */
        $helper = Mage::helper('amaudit');
        $logModel = Mage::getModel('amaudit/log');
        $logData['date_time'] = Mage::getModel('core/date')->gmtDate();
        $username = Mage::getSingleton('admin/session')->getUser() ? Mage::getSingleton('admin/session')->getUser()->getUsername() : '';
        $logData['username'] = $username;
        if (in_array('exportPost', $arrPath)) {
            $logData['type'] = $arrPath[2];
        }
        $logData['type'] = $arrPath[3];
        $category = $arrPath[2];
        $logData['category'] = $category;
        $logData['category_name'] = Mage::helper('amaudit')->getCatNameFromArray($category);
        $logData['parametr_name'] = 'back';
        $logData['element_id'] = 0;
        $logData['info'] = $helper->__('Data was exported');
        $logData['store_id'] = $observer->getStoreId();
        $logModel->setData($logData);
        $logModel->save();
    }

    protected function _deleteProduct($object)
    {
        /** @var Amasty_Audit_Helper_Data $helper */
        $helper = Mage::helper('amaudit');
        $logModel = Mage::getModel('amaudit/log')->load(Mage::registry('amaudit_log_id'));
        $username = Mage::getSingleton('admin/session')->getUser() ? Mage::getSingleton('admin/session')->getUser()->getUsername() : '';
        $this->_logData = $logModel->getData();
        $name = $object->getName();
        if ($logModel) {
            $logData['info'] = $name;
            $logData['element_id'] = $object->getEntityId();
            $logData['type'] = 'Delete';
            $ogData['category'] = "admin/catalog_product";
            $logData['category_name'] = $helper->__('Product');
            $logData['parametr_name'] = "delete";
            $logData['store_id'] = 0;
            $logData['username'] = $username;
            $logData['date_time'] = Mage::getModel('core/date')->gmtDate();
            $logModel->setData($logData);
            $logModel->save();
        }
    }

    protected function _rulesToOptionArray($rulesCollection)
    {
        $rulesOptionsArray = array();
        foreach ($rulesCollection as $rule) {
            $rulesOptionsArray['rule: ' . $rule->getResourceId()] = $rule->getPermission();
        }
        return $rulesOptionsArray;
    }

    protected function _isProductMassUpdate()
    {
        $isProductMassUpdate = false;
        $backtrace = debug_backtrace();
        foreach ($backtrace as $step) {
            if ($step['class'] == 'Mage_Catalog_Model_Product_Action' && $step['function'] == 'updateAttributes') {
                $isProductMassUpdate = true;
            }
        }

        return $isProductMassUpdate;
    }

    private function _saveLog()
    {
        if (!Mage::app()->getStore()->isAdmin()) {
            return false;
        }
        //save log start
        $username = Mage::getSingleton('admin/session')->getUser() ? Mage::getSingleton('admin/session')->getUser()->getUsername() : '';
        $path = Mage::registry('amaudit_log_path');
        $arrPath = ($path) ? explode("/", $path) : array();
        if (!array_key_exists(3, $arrPath)) {
            return false;
        }
        $logModel = Mage::getModel('amaudit/log');
        $this->_logData = array();
        $this->_logData['date_time'] = Mage::getModel('core/date')->gmtDate();
        $this->_logData['username'] = $username;
        if ("delete" == $arrPath[3]) {
            $this->_logData['type'] = "Delete";
        } else {
            $this->_logData['type'] = $arrPath[3];
        }
        $this->_logData['category'] = $arrPath[1] . '/' . $arrPath[2];
        $this->_logData['category_name'] = Mage::helper('amaudit')->getCatNameFromArray($this->_logData['category']);

        if (isset($arrPath[4])) {
            if ($arrPath[4] == 'store') $arrPath[4] = $arrPath[6];
            $paramName = $arrPath[4] == "key" ? "underfined" : $arrPath[4];
            if ($paramName == 'section') {
                $paramName .= '/' . $arrPath[5];
            }
            $this->_logData['parametr_name'] = $paramName;
        }

        $storeId = 0;
        if ($keyStore = array_search("store", $arrPath)) {
            $storeId = $arrPath[$keyStore + 1];
            if (!is_numeric($storeId)) {
                $storeId = Mage::getModel('core/store')->load($storeId, 'code')->getStoreId();
            }
        }
        $this->_logData['store_id'] = $storeId;

        if ($this->_logData['type'] != 'logout') {
            $logModel->setData($this->_logData);
            //for better work with orders
            if ($this->_logData['type'] != 'loadBlock') {
                $logModel->save();
            }
            Mage::register('amaudit_log_id', $logModel->getEntityId(), true);
            Mage::unregister('amaudit_details_before');
        } elseif ($this->_isFirstLogout) {
            $this->_isFirstLogout = false;
            $detailsModel = Mage::getModel('amaudit/data');
            $detailsModel->saveLogoutData($this->_logData);
        }
        //save log end
    }

    protected function _removeEmptyFields($array)
    {
        foreach ($array as $key => $value) {
            if (empty($value)) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    /**
     * Save details for configurations that are processed only before or only after saving
     * @param $entity - entity with old value
     * @param null $observer - entity with new value
     */
    protected function _saveIfNoDetails($entity, $observer = NULL)
    {
        $configPaths = array(
            'dev/translate_inline/active',
            'dev/translate_inline/active_admin',
            'dev/log/active',
            'dev/log/file',
            'dev/log/exception_file',
        );

        if ($entity instanceof Mage_Core_Model_Config_Data) {
            $path = $entity->getPath();
            if (in_array($path, $configPaths)) {
                if (is_null($observer)) {
                    $newValue = Mage::getStoreConfig($path);
                } else {
                    $newValue = $observer->getObject()->getValue();
                }
                $massOld = array($entity->getPath() => $entity->getValue());
                $massNew = array($path => $newValue);
                $this->_saveDetails(
                    $massOld,
                    $massNew,
                    Mage::registry('amaudit_log_id'),
                    false,
                    'Mage_Core_Model_Config_Data'
                );

            }
        }
    }

    protected function _handleEmptyElements($massOld, $massNew)
    {
        $mandatoryValuesString = Mage::getStoreConfig('amaudit/log/mandatory_values');
        $mandatoryValuesString = str_replace("\r", "", $mandatoryValuesString);;

        $mandatoryValues = explode("\n", $mandatoryValuesString);
        foreach ($mandatoryValues as $value) {
            if (array_key_exists($value, $massNew) && !array_key_exists($value, $massOld)) {
                $massOld[$value] = '';
            }
        }

        return $massOld;
    }

    protected function _isAmpgrid()
    {
        if (is_null($this->_isAmpgrid)) {
            $this->_isAmpgrid = false;
            $backTrace = debug_backtrace();
            foreach ($backTrace as $step) {
                if (isset($step['class']) && ($step['class'] == 'Amasty_Pgrid_Adminhtml_Ampgrid_FieldController')
                    && ($step['function'] == '_updateProductData')) {
                    $this->_isAmpgrid = true;
                    break;
                }
            }
            $backTrace = NULL;
        }

        return $this->_isAmpgrid;
    }

    protected function _checkOrder($logModel, $object)
    {
        $isNoCompleteOrder = false;
        if ($object instanceof Mage_Sales_Model_Quote) {
            $protectCode = $object->getProtectCode();
            if (empty($protectCode)) {
                $isNoCompleteOrder = true;
            }
        } elseif ($object instanceof Mage_Sales_Model_Quote_Address
            || $object instanceof Mage_Sales_Model_Quote_Payment
        ) {
            $logId = $logModel->getEntityId();
            $logDetailsCollection = Mage::getModel('amaudit/log_details')->getCollection()
                ->addFilter('log_id', $logId);
            $logData = $logDetailsCollection->getData();
            if (empty($logData)) {
                $isNoCompleteOrder = true;
            }

        }

        return $isNoCompleteOrder;
    }

    private function _saveDetails($massOld, $massNew, $logId, $isNew = false, $object = null)
    {
        $model = null;

        if (is_object($object)) {
            $model = get_class($object);
        }

        $notSaveModels = array(
            'Mage_SalesRule_Model_Coupon',
            'Mage_Eav_Model_Entity_Store',
            'Mage_Catalog_Model_Product_Type_Configurable_Attribute'
        );
        if ($isNew) {
            $massOld = $this->_removeEmptyFields($massNew);
        }
        if (!in_array($model, $notSaveModels)) {
            try {
                $notRestore = array('entity_id', 'entity_type_id');
                if (is_array($massOld)) {

                    if (is_a($object, 'Mage_Catalog_Model_Product')) {
                        foreach ($this->_productLinkData as $linkData) {
                            if (isset($massNew[$linkData])) {
                                $oldIds = implode(',', Mage::registry('amaudit_' . $linkData));
                                $newIds = implode(',', array_keys($massNew[$linkData]));
                                $massNew[$linkData] = $newIds;
                                $massOld[$linkData] = $oldIds;
                            }
                        }

                        unset($massOld['store_id']);
                        $oldWebsiteIds = Mage::registry('amaudit_old_product_websites_ids');
                        if (is_array($oldWebsiteIds)) {
                            $massOld['website_ids'] = implode(',', $oldWebsiteIds);
                        }
                        if (isset ($massNew['website_ids'])) {
                            if (is_array($massNew['website_ids'])) {
                                $massNew['website_ids'] = implode(',', $massNew['website_ids']);
                            }
                        } else {
                            $massNew['website_ids'] = $massOld['website_ids'];
                        }
                    }

                    if (($model == 'Mage_Admin_Model_User')) {
                        //for change the user's role
                        if (isset($massNew['roles']['0'])) {
                            $newRoleId = $massNew['roles']['0'];
                            $oldRoleIdPrepare = explode('=', $massNew['user_roles']);
                            $oldRoleId = $oldRoleIdPrepare[0];
                            $rolesModel = Mage::getModel('admin/role');
                            $oldRole = $rolesModel->load($oldRoleId)->getRoleName();
                            $newRole = $rolesModel->load($newRoleId)->getRoleName();
                            $massNew['roles'] = $newRole;
                            $massOld['roles'] = $oldRole;
                        }
                        //compatitility with Amasty_Perm
                        if ('true' == (string)Mage::getConfig()->getNode('modules/Amasty_Perm/active')) {
                            if (isset($massNew['selected_customers'])) {
                                $oldCustomerIds = Mage::getModel('amperm/mysql4_perm')->getCustomerIds($massNew['user_id']);
                                $massOld['selected_customers'] = implode('&', $oldCustomerIds);
                            }
                            if (isset($massNew['customer_group_id']) && is_array($massNew['customer_group_id'])) {
                                $massNew['customer_group_id'] = implode($massNew['customer_group_id'], ',');
                            } elseif (isset($massOld['customer_group_id'])) {
                                $massNew['customer_group_id'] = '';
                            }
                        }
                    }

                    $massOld = $this->_handleEmptyElements($massOld, $massNew);
                    foreach ($massOld as $key => $value) {
                        if (in_array($key, $notRestore)) {
                            continue;
                        }
                        if ($key == 'image') {
                            if (isset($massNew[$key]) && is_array($massNew[$key])) {
                                $massNew[$key] = array_shift($massNew[$key]);
                            }
                        }
                        if (array_key_exists($key, $massNew) && $key != 'updated_at' && $key != 'created_at' && $key != 'category_name') {
                            if (($value != $massNew[$key] && !(!$value && !$massNew[$key])) || $isNew) {
                                $detailsModel = Mage::getModel('amaudit/log_details');
                                if ($model != 'Mage_Sales_Model_Order_Item' && $detailsModel->isInCollection($logId, $key, $model)) {
                                    continue;
                                }
                                $detailsModel = $this->_setDetailsData($isNew, $detailsModel, $value, $massNew, $key, $logId, $model);
                                if (!is_array($key) && ($key !== "media_gallery")) $detailsModel->save();
                            } else if (is_array($value) && is_array($massNew[$key])) {
                                if ($key == 'media_gallery') {
                                    foreach ($value['images'] as $image) {
                                        $value[] = $image['file'];
                                    }
                                    foreach ($massNew[$key]['images'] as $newImage) {
                                        $massNew[$key][] = $newImage['file'];
                                    }
                                    unset($value['images']);
                                    unset($value['values']);
                                    unset($massNew[$key]['images']);
                                    unset($massNew[$key]['values']);
                                }
                                $old = $this->implode_r(',', $value);
                                $new = $this->implode_r(',', $massNew[$key]);
                                if ($old != $new || $isNew) {
                                    $detailsModel = Mage::getModel('amaudit/log_details');
                                    $detailsModel = $this->_setDetailsData($isNew, $detailsModel, $value, $massNew, $key, $logId, $model);
                                    $detailsModel->save();
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage());
                Mage::logException($e);
            }
        }
    }

    private function _setDetailsData($isNew, $detailsModel, $value, $massNew, $key, $logId, $model)
    {
        if ($key == 'categories') {
            $value = $this->_prepareProductCategories($value);
            $massNew['categories'] = $this->_prepareProductCategories($massNew['categories']);
        }
        if (is_array($value)) {
            $value = 'is_array';
        }
        if (is_array($massNew[$key])) {
            $massNew[$key] = 'is_array';
        }
        if (is_array($key)) {
            $key = 'is_array';
        }
        if (is_array($logId)) {
            $logId = 'is_array';
        }
        if (is_array($model)) {
            $model = 'is_array';
        }
        if (is_object($massNew[$key])) {
            $massNew[$key] = get_class($massNew[$key]);
        }
        if($key == 'price') {
            $value = $this->_convertPrice($value);
            $massNew[$key] = $this->_convertPrice($massNew[$key]);
        }

        if (!$isNew) $detailsModel->setData('old_value', $value);
        $detailsModel->setData('new_value', $massNew[$key]);
        $detailsModel->setData('name', $key);
        $detailsModel->setData('log_id', $logId);
        $detailsModel->setData('model', $model);
        return $detailsModel;
    }

    /**
     * @param $price
     * @return string
     */
    protected function _convertPrice($price)
    {
        return number_format($price, 2, '.', '');
    }

    private function _prepareProductCategories($categoryIds)
    {
        $categoryNames = '';

        if (is_array($categoryIds)) {
            foreach ($categoryIds as $id) {
                $categoryNames[] = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId())->load($id)->getName();
            }

            if (is_array($categoryNames)) {
                $categoryNames = implode(', ', $categoryNames);
            }
        }

        return $categoryNames;
    }

    private function _saveCompilation($path, $username)
    {
        /** @var Amasty_Audit_Helper_Data $helper */
        $helper = Mage::helper('amaudit');
        if (strpos($path, "compiler/process") !== false
            || strpos($path, "compiler_process") !== false
        ) {
            $arrPath = explode("/", $path);
            $keyStore = array_search("process", $arrPath);
            if (!$keyStore) {
                $keyStore = array_search("compiler_process", $arrPath);
            }
        }

        if (isset($arrPath) && isset($keyStore)) {
            $type = $arrPath[$keyStore + 1];
            if ($type != "index") {
                try {
                    $logModel = Mage::getModel('amaudit/log');
                    $this->_logData = array();
                    $this->_logData['date_time'] = Mage::getModel('core/date')->gmtDate();
                    $this->_logData['username'] = $username;
                    $this->_logData['type'] = ucfirst($type);
                    $this->_logData['category'] = "compiler/process";
                    $this->_logData['category_name'] = $helper->__('Compilation');
                    $this->_logData['parametr_name'] = 'index';
                    $this->_logData['info'] = $helper->__('Compilation');
                    $storeId = 0;
                    if ($keyStore = array_search("store", $arrPath)) {
                        $storeId = $arrPath[$keyStore + 1];
                    }
                    $this->_logData['store_id'] = $storeId;
                    $logModel->setData($this->_logData);
                    $logModel->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                    Mage::log($e->getMessage());
                }
            }
        }
    }

    private function _saveCache($path, $username)
    {
        /** @var Amasty_Audit_Helper_Data $helper */
        $helper = Mage::helper('amaudit');
        $params = Mage::app()->getRequest()->getParams();
        $adminPath = Mage::registry('amaudit_admin_path') ? Mage::registry('amaudit_admin_path') : 'admin';
        if (strpos($path, $adminPath . "/cache") !== false) {
            $arrPath = explode("/", $path);
            if ($keyStore = array_search("cache", $arrPath)) {
                $type = $arrPath[$keyStore + 1];
                if ($type != "index") {
                    try {
                        $logModel = Mage::getModel('amaudit/log');
                        $this->_logData = array();
                        $this->_logData['date_time'] = Mage::getModel('core/date')->gmtDate();
                        $this->_logData['username'] = $username;
                        $this->_logData['type'] = ucfirst($type);
                        $this->_logData['category'] = "admin/cache";
                        $this->_logData['category_name'] = $helper->__('Cache');
                        $this->_logData['parametr_name'] = 'index';
                        $this->_logData['info'] = $helper->__('Cache');
                        $storeId = 0;
                        if ($keyStore = array_search("store", $arrPath)) {
                            $storeId = $arrPath[$keyStore + 1];
                        }
                        $this->_logData['store_id'] = $storeId;

                        $logModel->setData($this->_logData);
                        $logModel->save();
                        if (array_key_exists('types', $params)) {
                            $params = Mage::helper('amaudit')->getCacheParams($params['types']);
                            $this->_saveDetails($params, array(), $logModel->getEntityId(), true);
                        }
                    } catch (Exception $e) {
                        Mage::logException($e);
                        Mage::log($e->getMessage());
                    }
                }
            }
        }
    }

    private function _saveIndex($path, $username)
    {
        /** @var Amasty_Audit_Helper_Data $helper */
        $helper = Mage::helper('amaudit');
        $params = Mage::app()->getRequest()->getParams();
        $adminPath = Mage::registry('amaudit_admin_path') ? Mage::registry('amaudit_admin_path') : 'admin';
        if (strpos($path, $adminPath . "/process") !== false) {
            $arrPath = explode("/", $path);
            if ($keyStore = array_search("process", $arrPath)) {   //settings log or not user
                $type = $arrPath[$keyStore + 1];
                if ($type != "list") {
                    try {
                        $logModel = Mage::getModel('amaudit/log');
                        $this->_logData = array();
                        $this->_logData['date_time'] = Mage::getModel('core/date')->gmtDate();
                        $this->_logData['username'] = $username;
                        $this->_logData['type'] = ucfirst($type);
                        $this->_logData['category'] = "admin/process";
                        $this->_logData['category_name'] = $helper->__('Index Management');
                        $this->_logData['parametr_name'] = 'list';
                        $this->_logData['info'] = $helper->__('Index Management');
                        $storeId = 0;
                        if ($keyStore = array_search("store", $arrPath)) {
                            $storeId = $arrPath[$keyStore + 1];
                        }
                        $this->_logData['store_id'] = $storeId;

                        $logModel->setData($this->_logData);
                        $logModel->save();
                        if (array_key_exists('process', $params)) {
                            $params = Mage::helper('amaudit')->getIndexParams($params['process']);
                            $this->_saveDetails($params, array(), $logModel->getEntityId(), true);
                        }
                    } catch (Exception $e) {
                        Mage::logException($e);
                        Mage::log($e->getMessage());
                    }
                }
            }
        }
    }
}
