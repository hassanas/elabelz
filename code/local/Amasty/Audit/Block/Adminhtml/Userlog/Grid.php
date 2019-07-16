<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */
class Amasty_Audit_Block_Adminhtml_Userlog_Grid extends Amasty_Audit_Block_Adminhtml_DefaultLog
{
    protected function _prepareCollection()
    {
        /**
         * @var Amasty_Audit_Model_Mysql4_Log_Collection $collection
         */
        $collection = Mage::getModel('amaudit/log')->getCollection();
        $collection->getSelect()
            ->joinLeft(array('r' => Mage::getSingleton('core/resource')->getTableName('amaudit/log_details')), 'main_table.entity_id = r.log_id', array('is_logged' => 'MAX(r.log_id)'))
            ->joinLeft(array('u' => Mage::getSingleton('core/resource')->getTableName('admin/user')), 'main_table.username = u.username', array('fullname' => "CONCAT(firstname, ' ' ,lastname)"))
            ->group('main_table.entity_id')
        ;
        $collection->addFilterToMap('username', 'main_table.username');
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    public function addFilterToMap($collection,$column){
        $field = ( $column->getFilterIndex() ) ? $column->getFilterIndex() : $column->getIndex();
        $collection->addFilterToMap($field,$field." ");
        $cond = $column->getFilter()->getCondition();
        if ($field && isset($cond)) {
            $collection->addFieldToFilter($field , $cond);
        }
    }

    protected function _prepareColumns()
    {
        $this->addExportType('*/*/exportCsv', Mage::helper('amaudit')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('amaudit')->__('XML'));

        $hlp = Mage::helper('amaudit');

        $this->addColumn('date_time', array(
            'header'    => $hlp->__('Date'),
            'index'     => 'date_time',
            'type'      => 'datetime',
            'width'     => '170px',
        ))
        ;

        $this->addColumn('username', array(
            'header' => $hlp->__('Username'),
            'index'  => 'username',
            'align'  => 'center',
        ))
        ;

        $this->addColumn('fullname', array(
            'header'         => $hlp->__('Fullname'),
            'index'          => 'fullname',
            'align'          => 'center',
            'filter_index'   => 'CONCAT(firstname, \' \' ,lastname)',
            'filter_condition_callback' => array($this,"addFilterToMap")
        ))
        ;

        $this->addColumn('type', array(
            'header'         => $hlp->__('Action Type'),
            'index'          => 'type',
            'align'          => 'center',
            'frame_callback' => array($this, 'decorateStatus'),
        ))
        ;

        $this->addColumn('category_name', array(
            'header' => $hlp->__('Object'),
            'index'  => 'category_name',
        ))
        ;


        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'               => Mage::helper('cms')->__('Store View'),
                'index'                => 'store_id',
                'type'                 => 'store',
                'store_all'            => true,
                'store_view'           => true,
                'skipEmptyStoresLabel' => 1,
                'sortable'             => true,
            ))
            ;
        }

        $this->addColumn('info', array(
            'header'         => $hlp->__('Item'),
            'index'          => 'info',
            'align'          => 'center',
            'renderer'       => 'amaudit/adminhtml_auditlog_renderer_name',
            'frame_callback' => array($this, 'showOpenElementUrl')
        ))
        ;

        $this->addColumn('action',
            array(
                'header'         => $hlp->__('Actions'),
                'width'          => '170px',
                'align'          => 'center',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => array($this, 'showActions')
            ))
        ;


        return parent::_prepareColumns();
    }

}
