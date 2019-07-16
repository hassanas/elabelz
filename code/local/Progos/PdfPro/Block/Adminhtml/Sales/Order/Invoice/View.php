<?php
/**
* @category Progos_PdfPro
* @package Progos
* @author Saroop Chand <saroop.chand@progos.org>
*/
class Progos_PdfPro_Block_Adminhtml_Sales_Order_Invoice_View extends VES_PdfPro_Block_Adminhtml_Sales_Order_Invoice_View
{
    public function getPrintDestinationUrl(){
        return $this->getUrl('adminhtml/pdfpro_print/invoiceDestination',array('invoice_id'=>$this->getInvoice()->getId()));
    }
	/**
	 * Add PDF Pro Print button to view invoice page
	 * @see Mage_Core_Block_Abstract::_prepareLayout()
	 */
	protected function _prepareLayout(){
		if(!Mage::getStoreConfig('pdfpro/config/enabled')) return;
		$block = $this->getLayout()->getBlock('sales_invoice_view');
		if(!$block) return;
		if(Mage::getStoreConfig('pdfpro/config/remove_default_print')){
			$block->removeButton('print');
		}
		$block->addButton('pdfpro_print', array(
                'label'     => 'Easy PDF - '.Mage::helper('sales')->__('Print Invoice'),
                'class'     => 'save',
                'onclick'   => 'setLocation(\''.$this->getPrintUrl().'\')'
                )
            );
        $block->addButton('pdfpro_print_destination_invoice', array(
                'label'     => 'Easy PDF - '.Mage::helper('sales')->__('Print Destination Invoice'),
                'class'     => 'save',
                'onclick'   => 'setLocation(\''.$this->getPrintDestinationUrl().'\')'
                )
            );
        if(Mage::getStoreConfig('pdfpro/config/remove_default_print')){
            $block->removeButton('pdfpro_print');
        }
	}
}