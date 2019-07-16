<?php

class VES_PdfProCustomVariables_Model_Mysql4_Pdfprocustomvariables_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('pdfprocustomvariables/pdfprocustomvariables');
    }
}