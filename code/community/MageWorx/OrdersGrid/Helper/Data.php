<?php

/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_OrdersGrid_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_ENABLED                           = 'mageworx_ordersmanagement/ordersgrid/enabled';

    const XML_LAST_ORDERS_SYNC                  = 'mageworx_ordersmanagement/ordersgrid/last_orders_sync';

    const XML_ENABLE_INVOICE_ORDERS             = 'mageworx_ordersmanagement/ordersgrid/enable_invoice_orders';
    const XML_SEND_INVOICE_EMAIL                = 'mageworx_ordersmanagement/ordersgrid/send_invoice_email';
    const XML_ENABLE_SHIP_ORDERS                = 'mageworx_ordersmanagement/ordersgrid/enable_ship_orders';
    const XML_SEND_SHIPMENT_EMAIL               = 'mageworx_ordersmanagement/ordersgrid/send_shipment_email';
    const XML_ENABLE_ARCHIVE_ORDERS             = 'mageworx_ordersmanagement/ordersgrid/enable_archive_orders';
    const XML_ARCHIVE_ORDERS_STATUS             = 'mageworx_ordersmanagement/ordersgrid/archive_orders_status';
    const XML_DAYS                              = 'mageworx_ordersmanagement/ordersgrid/days_before_orders_get_archived';
    const XML_ENABLE_DELETE_ORDERS              = 'mageworx_ordersmanagement/ordersgrid/enable_delete_orders';
    const XML_HIDE_DELETED_ORDERS_FOR_CUSTOMERS = 'mageworx_ordersmanagement/ordersgrid/hide_deleted_orders_for_customers';
    const XML_ENABLE_DELETE_ORDERS_COMPLETELY   = 'mageworx_ordersmanagement/ordersgrid/enable_delete_orders_completely';
    const XML_DEFAULT_INVOICE_CAPTURE_CASE      = 'mageworx_ordersmanagement/ordersgrid/default_invoice_capture_case';

    const XML_GRID_COLUMNS                      = 'mageworx_ordersmanagement/ordersgrid/grid_columns';
    const XML_GRID_COLUMNS_SORT_ORDER           = 'mageworx_ordersmanagement/ordersgrid/grid_columns_sort_order';
    const XML_CUSTOMER_GRID_COLUMNS             = 'mageworx_ordersmanagement/ordersgrid/customer_grid_columns';
    const XML_CUSTOMER_GRID_COLUMNS_SORT_ORDER  = 'mageworx_ordersmanagement/ordersgrid/customer_grid_columns_sort_order';

    const XML_NUMBER_COMMENTS                   = 'mageworx_ordersmanagement/ordersgrid/number_comments';
    const XML_SHOW_THUMBNAILS                   = 'mageworx_ordersmanagement/ordersgrid/show_thumbnails';
    const XML_THUMBNAIL_HEIGHT                  = 'mageworx_ordersmanagement/ordersgrid/thumbnail_height';

    const XML_GRID_TYPE_ORDER                   = 'order';
    const XML_GRID_TYPE_CUSTOMER                = 'customer';

    protected $_contentType = 'application/octet-stream';
    protected $_resourceFile = null;
    protected $_handle = null;


    public function isEnabled()
    {
        return Mage::getStoreConfig(self::XML_ENABLED);
    }

    public function getLastOrdersSync()
    {
        return Mage::getStoreConfig(self::XML_LAST_ORDERS_SYNC);
    }

    public function isEnableInvoiceOrders()
    {
        return Mage::getStoreConfig(self::XML_ENABLE_INVOICE_ORDERS);
    }

    public function isSendInvoiceEmail()
    {
        return Mage::getStoreConfig(self::XML_SEND_INVOICE_EMAIL);
    }

    public function isEnableShipOrders()
    {
        return Mage::getStoreConfig(self::XML_ENABLE_SHIP_ORDERS);
    }

    public function isSendShipmentEmail()
    {
        return Mage::getStoreConfig(self::XML_SEND_SHIPMENT_EMAIL);
    }

    public function isEnableArchiveOrders()
    {
        return Mage::getStoreConfig(self::XML_ENABLE_ARCHIVE_ORDERS);
    }

    public function isEnableDeleteOrders()
    {
        return Mage::getStoreConfig(self::XML_ENABLE_DELETE_ORDERS);
    }

    public function isHideDeletedOrdersForCustomers()
    {
        return Mage::getStoreConfig(self::XML_HIDE_DELETED_ORDERS_FOR_CUSTOMERS);
    }

    public function isEnableDeleteOrdersCompletely()
    {
        return Mage::getStoreConfig(self::XML_ENABLE_DELETE_ORDERS_COMPLETELY);
    }

    /**
     * @return int
     */
    public function getDaysBeforeOrderGetArchived()
    {
        return intval(Mage::getStoreConfig(self::XML_DAYS));
    }

    /**
     * @param string $type
     * @return array
     */
    public function getGridColumns($type)
    {
        if ($type != self::XML_GRID_TYPE_CUSTOMER) {
            $type = self::XML_GRID_TYPE_ORDER;
            $listColumns = Mage::getStoreConfig(self::XML_GRID_COLUMNS);
        } else {
            $listColumns = Mage::getStoreConfig(self::XML_CUSTOMER_GRID_COLUMNS);
        }
        if (!$listColumns) {
            $listColumns = Mage::getSingleton('mageworx_ordersgrid/grid')->initColumns($type);
        }
        $listColumns = explode(',', $listColumns);
        return $listColumns;
    }

    /**
     * @return array
     */
    public function getAllGridColumns()
    {
        $options = Mage::getModel('mageworx_ordersgrid/system_config_source_orders_grid')->toArray();
        return $options;
    }

    /**
     * @return array
     */
    public function getGridColumnsSortOrder($type)
    {
        if ($type != self::XML_GRID_TYPE_CUSTOMER) {
            $type = self::XML_GRID_TYPE_ORDER;
            $data = Mage::getStoreConfig(self::XML_GRID_COLUMNS_SORT_ORDER);
        } else {
            $data = Mage::getStoreConfig(self::XML_CUSTOMER_GRID_COLUMNS_SORT_ORDER);
        }
        if (!$data) {
            $unsData = Mage::getSingleton('mageworx_ordersgrid/grid')->initColumnOrder($type);
        } else {
            $unsData = unserialize($data);
        }
        return $unsData;
    }

    /**
     * @return array
     */
    public function getColumnSettings($type)
    {
        $result = array();
        $selectedGridColumns = $this->getGridColumns($type);
        $sortOrder = $this->getGridColumnsSortOrder($type);
        $labels = Mage::getModel('mageworx_ordersgrid/system_config_source_orders_grid')->getLabels();
        foreach($sortOrder as $key => $value) {
            $enabled = 0;
            if (isset($labels[$key])) {
                $label = $labels[$key];
            } else {
                $label = $key;
            }
            if (in_array($key, $selectedGridColumns)) {
                $enabled = 1;
            }
            $array = array(
                'key' => $key,
                'label' => $label,
                'sortOrder' => $value,
                'enabled' => $enabled
            );
            $result[] = $array;
        }

        return $result;
    }

    public function getNumberComments()
    {
        return intval(Mage::getStoreConfig(self::XML_NUMBER_COMMENTS));
    }

    public function isShowThumbnails()
    {
        return Mage::getStoreConfig(self::XML_SHOW_THUMBNAILS);
    }

    public function getThumbnailHeight()
    {
        return Mage::getStoreConfig(self::XML_THUMBNAIL_HEIGHT);
    }

    /**
     * @return array
     */
    public function getArchiveOrderStatuses()
    {
        return explode(',', Mage::getStoreConfig(self::XML_ARCHIVE_ORDERS_STATUS));
    }

    /** Return string with default status of invoice.
     *  Values like: online, offline, not capture...
     * @return string | null
     */
    public function getDefaultInvoiceCaptureCase()
    {
        return Mage::getStoreConfig(self::XML_DEFAULT_INVOICE_CAPTURE_CASE);
    }

    /**
     * Assign orders to group (order_group_id attr.)
     *
     * @param array $orderIds
     * @param int $orderGroupId
     * @return int
     */
    public function addToOrderGroup($orderIds, $orderGroupId = 0)
    {
        $count = 0;
        if (!is_array($orderIds) || empty($orderIds)) {
            return $count;
        }

        /** @var MageWorx_OrdersGrid_Model_Order_Group $groupModel */
        $groupModel = Mage::getModel('mageworx_ordersgrid/order_group');
        $groupModel->setId($orderGroupId);
        $groupModel->setGroupToOrders($orderIds);
        $count = count($orderIds);

        Mage::getModel('mageworx_ordersgrid/order_grid')->syncOrderById($orderIds);

        return $count;
    }

    /**
     * Cancel & Delete orders by id completely (from database)
     *
     * @param array $orderIds
     * @return int
     */
    public function deleteOrderCompletely($orderIds)
    {
        $count = 0;
        if (!is_array($orderIds) || empty($orderIds)) {
            return $count;
        }

        foreach ($orderIds as $orderId) {
            Mage::getModel('mageworx_ordersgrid/order')->deleteOrderCompletelyById($orderId);
        }
        $count = count($orderIds);

        return $count;
    }

    /**
     * Mass invoice orders by ids
     *
     * @param array $orderIds
     * @return int
     */
    public function invoiceOrderMass($orderIds)
    {
        $count = 0;
        if (!is_array($orderIds) || empty($orderIds)) {
            return $count;
        }

        $count = Mage::getModel('mageworx_ordersgrid/order_invoice')->invoiceOrders($orderIds);

        return $count;
    }

    public function shipOrder($orderIds)
    {
        $count = 0;
        if (!is_array($orderIds) || empty($orderIds)) {
            return $count;
        }

        $count = Mage::getModel('mageworx_ordersgrid/order_shipment')->shipOrders($orderIds);

        return $count;
    }

    /**
     * translate and QuoteEscape
     *
     * @param $str
     * @return mixed
     */
    public function __js($str)
    {
        return $this->jsQuoteEscape(str_replace("\'", "'", $this->__($str)));
    }

    /**
     * @return bool
     */
    public function isMagentoEnterprise()
    {
        $isEnterprise = false;
        $i = Mage::getVersionInfo();
        if ($i['major'] == 1) {
            if (method_exists('Mage', 'getEdition')) {
                if (Mage::getEdition() == Mage::EDITION_ENTERPRISE) {
                    $isEnterprise = true;
                }
            } elseif ($i['minor'] > 7) {
                $isEnterprise = true;
            }
        }
        return $isEnterprise;
    }

    /**
     * @return string
     */
    public function getMagentoVersion()
    {
        $i = Mage::getVersionInfo();
        if ($i['major'] == 1 && $this->isMagentoEnterprise()) {
            $i['minor'] -= 5;
        }
        return trim("{$i['major']}.{$i['minor']}.{$i['revision']}" . ($i['patch'] != '' ? ".{$i['patch']}" : "") . "-{$i['stability']}{$i['number']}", '.-');
    }

    /**
     * Check module and class (optional)
     *
     * @param  string $module
     * @param  null|string $class
     * @param null $rewriteClass
     * @return bool
     */
    public static function foeModuleCheck($module, $class = null, $rewriteClass = null)
    {
        $module = (string)$module;
        if ($module && (string)Mage::getConfig()->getModuleConfig($module)->active == 'true') {
            if ($class && $rewriteClass) {
                return is_subclass_of($class, $rewriteClass);
            } elseif ($class && !$rewriteClass) {
                return class_exists($class);
            }
            return true;
        }
        return false;
    }

    /**
     * Save serialized sort order data is system config
     *
     * @param string $for - customer or order
     * @param array $data
     * @return bool
     */
    public function saveSortOrderConfig($for, $data)
    {
        switch ($for) {
            case 'customer' :
                $path = self::XML_CUSTOMER_GRID_COLUMNS_SORT_ORDER;
                break;
            case 'order' :
                $path = self::XML_GRID_COLUMNS_SORT_ORDER;
                break;
        }

        if (!isset($path)) {
            return false;
        }

        $value = serialize($data);

        /** @var Mage_Core_Model_Config $config */
        $config = Mage::getSingleton('core/config');
        $config->saveConfig($path, $value);
        Mage::app()->getCacheInstance()->cleanType('config');

        return true;
    }

    /**
     * Save selected columns data is system config
     *
     * @param string $for - customer or order
     * @param array $data
     * @return bool
     */
    public function saveColumnsConfig($for, $data)
    {
        switch ($for) {
            case 'customer' :
                $path = self::XML_CUSTOMER_GRID_COLUMNS;
                break;
            case 'order' :
                $path = self::XML_GRID_COLUMNS;
                break;
        }

        if (!isset($path)) {
            return false;
        }

        /** @var Mage_Core_Model_Config $config */
        $config = Mage::getSingleton('core/config');
        $config->saveConfig($path, $data);
        Mage::app()->getCacheInstance()->cleanType('config');

        return true;
    }

    /**
     * @return array|mixed
     */
    public function getCountryNames()
    {
        if (Mage::registry('country_names')) {
            return Mage::registry('country_names');
        }

        $countryNames = array();
        $collection = Mage::getResourceModel('directory/country_collection')->load();
        foreach ($collection as $item) {
            if ($item->getCountryId()) {
                $countryNames[$item->getCountryId()] = $item->getName();
            }
        }
        asort($countryNames);
        Mage::register('country_names', $countryNames);

        return $countryNames;
    }

    /**
     * Get last orders sync time
     *
     * @return string
     */
    public function getLastOrdersSyncTime()
    {
        $time = $this->getLastOrdersSync();
        if(!$time){
            return '';
        }
        return date('F d, Y / h:i', $time);
    }
}