<?php
class Progos_PdfPro_Block_Adminhtml_Sales_Order_View extends VES_PdfPro_Block_Adminhtml_Sales_Order_View
{
    public function getPrintTaxUrl(){
        return $this->getUrl('adminhtml/pdfpro_print/taxdestination',array('tax_order_id'=>$this->getOrder()->getId()));
    }

    /**
     * Add PDF Pro Print button to view invoice page
     * @see Mage_Core_Block_Abstract::_prepareLayout()
     */
    protected function _prepareLayout(){
        if(!Mage::getStoreConfig('pdfpro/config/enabled') || !Mage::getStoreConfig('pdfpro/config/admin_print_order')) return;
        $block = $this->getLayout()->getBlock('sales_order_edit');
        if($block) {
            $block->addButton('pdfpro_print', array(
                'label' => Mage::helper('sales')->__('Destination/COD Invoice'),
                'class' => 'save',
                'onclick' => 'setLocation(\'' . $this->getPrintUrl() . '\')'
            ), 0, 300, 'header', 'header'
            );
            // adding tax invoice button
            $block->addButton('pdfpro_tax_print', array(
                    'label'     => Mage::helper('sales')->__('Print Tax Invoice'),
                    'class'     => 'save',
                    'onclick'   => 'setLocation(\''.$this->getPrintTaxUrl().'\')'
                ), 0, 900, 'header', 'header'
            );
        }
    }
}