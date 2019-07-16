<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */

class Infomodus_Dhllabel_Block_Adminhtml_Lists_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('dhllabelGrid');
        $this->setDefaultSort('created_time');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('dhllabel/dhllabel')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'label_id',
            array(
                'header' => Mage::helper('dhllabel')->__('ID'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'label_id',)
        );

        $this->addColumn(
            'trackingnumber',
            array(
                'header' => Mage::helper('dhllabel')->__('Tracking Number'),
                'align' => 'left',
                'width' => '250px',
                'index' => 'trackingnumber',
                'frame_callback' => array($this, 'callback_new_label_link'),
            )
        );

        $this->addColumn(
            'order_id',
            array(
                'header' => Mage::helper('dhllabel')->__('Order ID'),
                'align' => 'left',
                'width' => '50px',
                'index' => 'order_id',
                'frame_callback' => array($this, 'callback_order_link'),
                'filter_condition_callback' => array($this, '_filterOrderId'),)
        );

        $this->addColumn(
            'shipment_id',
            array(
                'header' => Mage::helper('dhllabel')->__('Shipment or Credit memos ID'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'shipment_id',
                'frame_callback' => array($this, 'callback_ship_or_credit_link'),
                'filter_condition_callback' => array($this, '_filterShipmentId'),)
        );

        $this->addColumn(
            'customer_name', array(
                'header' => Mage::helper('dhllabel')->__('Customer name'),
                'align' => 'left',
                'width' => '80px',
                'filter' => false,
                'sortable' => false,
                'frame_callback' => array($this, 'callback_customer_name'),)
        );

        $this->addColumn(
            'labelname', array(
                'header' => Mage::helper('dhllabel')->__('Print'),
                'align' => 'left',
                'width' => '120px',
                'index' => 'labelname',
                'frame_callback' => array($this, 'callback_print'),)
        );

        $this->addColumn(
            'type', array(
                'header' => Mage::helper('dhllabel')->__('Type'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'type',
                'type' => 'options',
                'options' => Mage::getModel('dhllabel/config_listsType')->getTypes(),
                'frame_callback' => array($this, 'callback_withtype2'),
                'filter_condition_callback' => array($this, '_listsTypeFilter'),)
        );

        $this->addColumn(
            'statustext', array(
                'header' => Mage::helper('dhllabel')->__('Status'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'statustext',
                'type' => 'options',
                'options' => Mage::getModel('dhllabel/config_statuslabels')->getListsStatus(),
                'frame_callback' => array($this, 'callback_statustext'),
                'filter_condition_callback' => array($this, '_listsUpsStatusFilter'),)
        );

        $this->addColumn(
            'price', array(
                'header' => Mage::helper('dhllabel')->__('Price'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'price',
                'filter' => false,
                'sortable' => false,
                'frame_callback' => array($this, 'callback_price'),)
        );

        $this->addColumn(
            'created_time', array(
                'header' => Mage::helper('dhllabel')->__('Created date'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'created_time',)
        );

        $this->addColumnAfter('dhlstatus', array(
            'header' => Mage::helper('sales')->__('Dhl Status'),
            'index' => 'dhlstatus',
            'type' => 'options',
            'width' => '70px',
            'options' => Mage::getModel('customorderflags/source_dhlstatus')->toOptionArray(true),
        ), 'created_time');

        $this->addColumn(
            'action',
            array(
                'header' => Mage::helper('dhllabel')->__('Action'),
                'width' => '100',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('dhllabel')->__('Delete'),
                        'url' => array('base' => '*/*/delete'),
                        'field' => 'id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,)
        );

        $this->addExportType('*/*/exportCsv', Mage::helper('dhllabel')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('dhllabel')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('label_id');
        $this->getMassactionBlock()->setFormFieldName('dhllabel');

        $this->getMassactionBlock()->addItem(
            'delete', array(
                'label' => Mage::helper('dhllabel')->__('Delete'),
                'url' => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('dhllabel')->__('Are you sure?'))
        );
        $this->getMassactionBlock()->addItem(
            'dhllabel_pdflabels', array(
                'label' => Mage::helper('sales')->__('Print Pdf Labels'),
                'url' => Mage::app()->getStore()->getUrl('adminhtml/dhllabel_pdflabels', array('type' => 'lists')),)
        );
        return $this;
    }

    public function callback_new_label_link($value, $row, $column, $isExport)
    {
        return $value . '<br> <a href="' . Mage::helper("adminhtml")->getUrl('adminhtml/dhllabel_dhllabel/intermediate/order_id/' . $row->getOrderId() . '/type/shipment') . '">Add new DHL Shipping Label</a>
        <br> <a href="' . Mage::helper("adminhtml")->getUrl('adminhtml/dhllabel_dhllabel/intermediate/order_id/' . $row->getOrderId() . '/type/refund') . '">Add new DHL Return Label</a>
        ';
    }

    public function callback_price($value, $row, $column, $isExport)
    {
        $price = Mage::getModel('dhllabel/labelprice')->getCollection()
            ->addFieldToFilter('order_id', $row->getOrderId())
            ->addFieldToFilter('shipment_id', $row->getShipmentId())->getFirstItem();
        return $price->getPrice();
    }

    public function callback_customer_name($value, $row, $column, $isExport)
    {
        $order = Mage::getModel('sales/order')->load($row->order_id);
        return $order->getCustomerName();
    }

    public function callback_print($value, $row, $column, $isExport)
    {
        if ($row->getStatus() == 1) {
            return;
        }

        $path = Mage::getBaseUrl('media') . 'dhllabel' . DS . "label" . DS;
        $pathDir = Mage::getBaseDir('media') . DS . 'dhllabel' . DS . "label" . DS;
        if ($row->getTypePrint() == 'pdf') {
            $Pdf = '<a href="' . $path . 'label_' . $row->getTrackingnumber() . '.pdf" target="_blank">PDF</a>';
        } else {
            if (Mage::getStoreConfig('dhllabel/printing/automatic_printing') == 1) {
                $Pdf = '<a href="' . $this->getUrl('adminhtml/dhllabel_dhllabel/autoprint/label_id/' . $row->getId()) . '" target="_blank">Print thermal</a>';
            } else {
                $printersText = Mage::getStoreConfig('dhllabel/printing/printer_name');
                $printers = explode(",", $printersText);
                $Pdf = '<a class="thermal-print-file" data-printer="' . (trim($printers[0])) . '" data-file="' . Mage::getBaseUrl('media') . 'dhllabel/label/' . $row->getLabelname() . '" href="#">Print thermal</a>';
            }
        }

        if (file_exists($pathDir . 'invoice_' . $row->getTrackingnumber() . '.pdf')) {
            $commercialInvoice = '<a href="' . $path . 'invoice_' . $row->getTrackingnumber() . '.pdf" target="_blank">Commercial Invoice</a>';
        }

        return $Pdf . (isset($commercialInvoice) ? " / " . $commercialInvoice : "");
    }

    public function callback_statustext($value, $row, $column, $isExport)
    {
        return $row->getStatustext();
    }

    public function callback_withtype2($value, $row, $column, $isExport)
    {
        $title = ucfirst($row->getType());
        switch ($row->getType() == $row->getType2() || $row->getType2() != "") {
            case false:
                $title .= " (" . $row->getType2() . ")";
                break;
        }

        return $title;
    }

    public function callback_order_link($value, $row, $column, $isExport)
    {
        $order = Mage::getModel("sales/order")->load($row->getOrderId());
        return '<a href="' . $this->getUrl('adminhtml/sales_order/view/order_id/' . $row->getOrderId()) . '">' . $order->getIncrementId() . '</a>';
    }

    public function callback_ship_or_credit_link($value, $row, $column, $isExport)
    {
        $path = 'adminhtml/sales_order_shipment/view/shipment_id/';
        $shipment = Mage::getModel("sales/order_shipment")->load($row->getShipmentId());
        if ($row->getType() == 'refund') {
            $path = 'adminhtml/sales_order_creditmemo/view/creditmemo_id/';
            $shipment = Mage::getModel("sales/order_creditmemo")->load($row->getShipmentId());
        }

        if ($row->getShipmentId() != 0) {
            return '<a href="' . $this->getUrl($path . $row->getShipmentId()) . '">' . $shipment->getIncrementId() . '</a>';
        } else {
            return '';
        }
    }

    public function _listsUpsStatusFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $status = 0;
        switch ($value) {
            case "success":
                $status = 0;
                break;
            case "error":
                $status = 1;
                break;
        }

        $collection->addFieldToFilter('status', $status);
        return $this;
    }

    public function _listsTypeFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $type = $value;
        $type2 = $value;
        switch ($value) {
            case "shipmentrefund":
                $type = 'shipment';
                $type2 = 'refund';
                break;
        }

        $collection->addFieldToFilter('type', $type);
        $collection->addFieldToFilter('type2', $type2);
        return $this;
    }

    public function _filterOrderId($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        /*$order = Mage::getModel("sales/order")->loadByIncrementId($value);
        if ($order == FALSE) {
            $order_id = $value;
        } else {
            $order_id = $order->getId();
        }
        $collection->addFieldToFilter('order_id', $order_id);*/
        $collection->getSelect()
            ->join(array('t4dhllabel' => Mage::getConfig()->getTablePrefix() . 'sales_flat_order'), 'main_table.order_id = t4dhllabel.entity_id AND (t4dhllabel.increment_id LIKE "%' . $value . '%" OR t4dhllabel.entity_id = ' . ((int)$value) . ')', null);
        return $this;
    }

    public function _filterShipmentId($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        /*$shipment = Mage::getModel("sales/order_shipment")->loadByIncrementId($value);
        if ($shipment == FALSE) {
        } else {
            $shipment_id = $shipment->getId();
        }

        $creditmemo = Mage::getModel("sales/order_creditmemo")->load($value, "increment_id");
        if ($creditmemo == FALSE) {
            $creditmemo_id = $value;
        } else {
            $creditmemo_id = $creditmemo->getId();
        }
        $collection->addFieldToFilter('shipment_id', array(array('eq' => $shipment_id), array('eq' => $creditmemo_id)));*/
        $collection->getSelect()
            ->joinLeft(array('t2dhllabel' => Mage::getConfig()->getTablePrefix() . 'sales_flat_shipment'), 'main_table.shipment_id = t2dhllabel.entity_id AND (t2dhllabel.increment_id LIKE "%' . $value . '%" OR t2dhllabel.entity_id = ' . ((int)$value) . ')', null)
            ->joinLeft(array('t3dhllabel' => Mage::getConfig()->getTablePrefix() . 'sales_flat_creditmemo'), 'main_table.shipment_id = t3dhllabel.entity_id AND (t3dhllabel.increment_id LIKE "%' . $value . '%" OR t3dhllabel.entity_id = ' . ((int)$value) . ')', null);
        $collection->getSelect()->where("t2dhllabel.entity_id IS NOT NULL OR t3dhllabel.entity_id IS NOT NULL");
        return $this;
    }
}