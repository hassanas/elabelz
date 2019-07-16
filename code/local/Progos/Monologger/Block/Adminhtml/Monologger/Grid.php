<?php

class Progos_Monologger_Block_Adminhtml_Monologger_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId("monologgerGrid");
        $this->setDefaultSort("id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel("monologger/monologger")->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn("id", array(
            "header" => Mage::helper("monologger")->__("ID"),
            "align" => "right",
            "width" => "50px",
            "type" => "number",
            "index" => "id",
        ));

        $this->addColumn("message", array(
            "header" => Mage::helper("monologger")->__("Message"),
            "index" => "message",
        ));
        $this->addColumn('level', array(
            'header' => Mage::helper('monologger')->__('Level'),
            'index' => 'level',
            'type' => 'options',
            'options' => Progos_Monologger_Block_Adminhtml_Monologger_Grid::getOptionArray2(),
            'renderer' => new Progos_Monologger_Block_Adminhtml_Monologger_Grid_Renderer_Level(),
            'filter_condition_callback' => array($this, '_filterLevelConditionCallback')
        ));


        $this->addColumn('datetime', array(
            'header' => Mage::helper('monologger')->__('Date Time'),
            'index' => 'datetime',
            'type' => 'datetime',
        ));
        $this->addColumn("ip", array(
            "header" => Mage::helper("monologger")->__("IP"),
            "index" => "ip",
        ));
        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return '#';
    }

    protected function _filterLevelConditionCallback($collection, $column)
    {
        if (!($value = $column->getFilter()->getValue())) {
            return;
        }
        $value = $column->getFilter()->getValue();
        $this->getCollection()->addFieldToFilter('level', $value);

        return $this;
    }


    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        $this->getMassactionBlock()->setUseSelectAll(true);
        $this->getMassactionBlock()->addItem('remove_monologger', array(
            'label' => Mage::helper('monologger')->__('Remove Monologger'),
            'url' => $this->getUrl('*/adminhtml_monologger/massRemove'),
            'confirm' => Mage::helper('monologger')->__('Are you sure?')
        ));
        return $this;
    }

    static public function getOptionArray2()
    {
        $data_array = array();
        $data_array[600] = 'EMERGENCY';
        $data_array[550] = 'ALERT';
        $data_array[500] = 'CRITICAL';
        $data_array[400] = 'ERROR';
        $data_array[300] = 'WARNING';
        $data_array[250] = 'NOTICE';
        $data_array[200] = 'INFO';
        $data_array[100] = 'DEBUG';
        return ($data_array);
    }

    static public function getValueArray2()
    {
        $data_array = array();
        foreach (Progos_Monologger_Block_Adminhtml_Monologger_Grid::getOptionArray2() as $k => $v) {
            $data_array[] = array('value' => $k, 'label' => $v);
        }
        return ($data_array);

    }


}