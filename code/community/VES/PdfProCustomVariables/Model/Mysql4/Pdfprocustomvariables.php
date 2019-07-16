<?php

class VES_PdfProCustomVariables_Model_Mysql4_Pdfprocustomvariables extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the pdfprocustomvariables_id refers to the key field in your database table.
        $this->_init('pdfprocustomvariables/pdfprocustomvariables', 'custom_variable_id');
    }
}