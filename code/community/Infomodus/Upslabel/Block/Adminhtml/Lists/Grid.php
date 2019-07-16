<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Upslabel_Block_Adminhtml_Lists_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('upslabelGrid');
        $this->setDefaultSort('created_time');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('upslabel/upslabel')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('upslabel_id', array(
            'header' => Mage::helper('upslabel')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'upslabel_id',
        ));

        $this->addColumn('trackingnumber', array(
            'header' => Mage::helper('upslabel')->__('Tracking Number'),
            'align' => 'left',
            'width' => '250px',
            'index' => 'trackingnumber',
            'frame_callback' => array($this, 'callback_new_label_link'),
        ));

        $this->addColumn('order_id', array(
            'header' => Mage::helper('upslabel')->__('Order ID'),
            'align' => 'left',
            'width' => '50px',
            'index' => 'order_id',
            'frame_callback' => array($this, 'callback_order_link'),
            'filter_condition_callback' => array($this, '_filterOrderId'),
        ));

        $this->addColumn('shipment_id', array(
            'header' => Mage::helper('upslabel')->__('Shipment or Credit memos ID'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'shipment_id',
            'frame_callback' => array($this, 'callback_ship_or_credit_link'),
            'filter_condition_callback' => array($this, '_filterShipmentId'),
        ));

        $this->addColumn('customer_name', array(
            'header' => Mage::helper('upslabel')->__('Customer name'),
            'align' => 'left',
            'width' => '80px',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => array($this, 'callback_customer_name'),
        ));

        $this->addColumn('labelname', array(
            'header' => Mage::helper('upslabel')->__('Print'),
            'align' => 'left',
            'width' => '120px',
            'index' => 'labelname',
            'frame_callback' => array($this, 'callback_print'),
        ));

        $this->addColumn('type', array(
            'header' => Mage::helper('upslabel')->__('Type'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'type',
            'type' => 'options',
            'options' => Mage::getModel('upslabel/config_listsType')->getTypes(),
        ));

        $this->addColumn('statustext', array(
            'header' => Mage::helper('upslabel')->__('Status'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'statustext',
            'type' => 'options',
            'options' => Mage::getModel('upslabel/config_statuslabels')->getListsStatus(),
            'frame_callback' => array($this, 'callback_statustext'),
            'filter_condition_callback' => array($this, '_listsUpsStatusFilter'),
        ));

        $this->addColumn('rva_printed', array(
            'header' => Mage::helper('upslabel')->__('Print status'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'rva_printed',
            'type' => 'options',
            'options' => array(Mage::helper('upslabel')->__("Unprinted"), Mage::helper('upslabel')->__("Printed")),
        ));

        $this->addColumn('created_time', array(
            'header' => Mage::helper('upslabel')->__('Created date'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'created_time',
            'type' => 'datetime'
        ));
        $this->addColumn('track_status', array(
            'header' => Mage::helper('upslabel')->__('Tracking status'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'track_status',
        ));

        $this->addColumnAfter('upsstatus', array(
            'header' => Mage::helper('sales')->__('Ups Status'),
            'index' => 'upsstatus',
            'type' => 'options',
            'width' => '70px',
            'options' => Mage::getModel('customorderflags/source_upsstatus')->toOptionArray(true),
        ), 'status');

        $this->addColumn('action',
            array(
                'header' => Mage::helper('upslabel')->__('Action'),
                'width' => '100',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('upslabel')->__('Delete'),
                        'url' => array('base' => '*/*/delete'),
                        'field' => 'id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,
            ));

        $this->addExportType('*/*/exportCsv', Mage::helper('upslabel')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('upslabel')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('upslabel_id');
        $this->getMassactionBlock()->setFormFieldName('upslabel');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('upslabel')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('upslabel')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('upslabel_pdflabels', array(
            'label' => Mage::helper('upslabel')->__('Print Pdf Labels'),
            'url' => Mage::app()->getStore()->getUrl('adminhtml/upslabel_pdflabels', array('type' => 'lists')),
        ));
        $this->getMassactionBlock()->addItem('upslabel_link_to_pdf', array(
            'label' => Mage::helper('upslabel')->__('Converting HTML return label to PDF format'),
            'url' => Mage::app()->getStore()->getUrl('adminhtml/upslabel_pdflabels/linktopdf'),
        ));
        return $this;
    }

    public function callback_print($value, $row, $column, $isExport)
    {
        if ($row->getStatus() == 1) {
            return '';
        }
        $HVR = false;
        $Html = '';
        $Image = '';
        $Invoice = '';
        $Pdf = "";
        if (file_exists(Mage::getBaseDir('media') . DS . 'upslabel' . DS . 'label' . DS . "HVR" . $row->getTrackingnumber() . ".html")) {
            $HVR = ' / <a href="' . Mage::getBaseUrl('media') . 'upslabel/label/HVR' . $row->getTrackingnumber() . '.html" target="_blank">HVR</a>';
        }
        if ($row->getTypePrint() == "GIF") {
            $Pdf = '<a href="' . $this->getUrl('adminhtml/upslabel_pdflabels/onepdf/label_id/' . $row->getId()) . '" target="_blank">PDF</a>';
            $Image = ' / <a href="' . $this->getUrl('adminhtml/upslabel_upslabel/print/imname/' . 'label' . $row->getTrackingnumber() . '.gif') . '" target="_blank">Image</a>';
        }
        elseif ($row->getTypePrint() == "link"){
            $Pdf = '<a href="' . $row->getLabelname() . '" target="_blank">' . Mage::helper('upslabel')->__('Print') . '</a>';
        }
        elseif ($row->getTypePrint() != "virtual"){
            if(Mage::getStoreConfig('upslabel/printing/automatic_printing') == 1) {
                $Pdf = '<a href="' . $this->getUrl('adminhtml/upslabel_upslabel/autoprint/label_id/' . $row->getId()) . '" target="_blank">' . Mage::helper('upslabel')->__('Print thermal') . '</a>';
            } else {
                $printersText = Mage::getStoreConfig('upslabel/printing/printer_name');
                $printers = explode(",", $printersText);
                $Pdf = '<a class="thermal-print-file" data-printer="'.(trim($printers[0])).'" data-file="' . Mage::getBaseUrl('media') . 'upslabel/label/' . $row->getLabelname() . '" href="' . $this->getUrl('adminhtml/upslabel_upslabel/autoprint/label_id/' . $row->getId()) . '">' . Mage::helper('upslabel')->__('Print thermal') . '</a>';
            }
        }
        if (file_exists(Mage::getBaseDir('media') . '/upslabel/label/' . $row->getTrackingnumber() . '.html')) {
            $Html = ' / <a href="' . Mage::getBaseUrl('media') . 'upslabel/label/' . $row->getTrackingnumber() . '.html" target="_blank">Html</a>';
        }
        if (file_exists(Mage::getBaseDir('media') . '/upslabel/inter_pdf/' . $row->getShipmentidentificationnumber() . '.pdf')) {
            $Invoice = ' / <a href="' . Mage::getBaseUrl('media') . 'upslabel/inter_pdf/' . $row->getShipmentidentificationnumber() . '.pdf" target="_blank">Invoice</a>';
        }
        return $Pdf .  $Html . $Image . $HVR.$Invoice;
    }

    public function callback_statustext($value, $row, $column, $isExport)
    {
        return $row->getStatustext();
    }

    public function callback_new_label_link($value, $row, $column, $isExport)
    {
        return $value.'<br> <a href="'.Mage::helper("adminhtml")->getUrl('adminhtml/upslabel_upslabel/intermediate/order_id/' . $row->getOrderId() . '/type/shipment').'">Add new UPS Shipping Label</a>
        <br> <a href="'.Mage::helper("adminhtml")->getUrl('adminhtml/upslabel_upslabel/intermediate/order_id/' . $row->getOrderId() . '/type/refund').'">Add new UPS Return Label</a>
        <br> <a href="'.Mage::helper("adminhtml")->getUrl('adminhtml/upslabel_upslabel/intermediate/order_id/' . $row->getOrderId() . '/type/invert').'">Add new UPS Invert Label</a>
        ';
    }

    public function callback_customer_name($value, $row, $column, $isExport)
    {
        $order = Mage::getModel('sales/order')->load($row->order_id);
        return $order->getCustomerName();
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

        return '<a href="' . $this->getUrl($path . $row->getShipmentId()) . '">' . $shipment->getIncrementId() . '</a>';
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
            ->join(array('t4upslabel' => Mage::getConfig()->getTablePrefix() . 'sales_flat_order'),'main_table.order_id = t4upslabel.entity_id AND (t4upslabel.increment_id LIKE "%'.$value.'%" OR t4upslabel.entity_id = '.((int)$value).')', NULL);
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
        }*/
        /*$collection->addFieldToFilter('shipment_id', array(array('eq' => $shipment_id), array('eq' => $creditmemo_id)));*/
        $collection->getSelect()
            ->joinLeft(array('t2upslabel' => Mage::getConfig()->getTablePrefix() . 'sales_flat_shipment'),'main_table.shipment_id = t2upslabel.entity_id AND (t2upslabel.increment_id LIKE "%'.$value.'%" OR t2upslabel.entity_id = '.((int)$value).')', NULL)
            ->joinLeft(array('t3upslabel' => Mage::getConfig()->getTablePrefix() . 'sales_flat_creditmemo'),'main_table.shipment_id = t3upslabel.entity_id AND (t3upslabel.increment_id LIKE "%'.$value.'%" OR t3upslabel.entity_id = '.((int)$value).')', NULL);
        $collection->getSelect()->where("t2upslabel.entity_id IS NOT NULL OR t3upslabel.entity_id IS NOT NULL");
        /*$query = $collection->getSelect();
            echo $query; exit;*/
        return $this;
    }
}