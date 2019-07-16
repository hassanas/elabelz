<?php

class Progos_Monologger_Block_Adminhtml_Monologger_Grid_Renderer_Level extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = trim((string)$row->getData($this->getColumn()->getIndex()));

        return $this->_logLevel($value);
    }

    protected function _logLevel($index)
    {
        $_levelMap = array(
            600 => 'EMERGENCY',
            550 => 'ALERT',
            500 => 'CRITICAL',
            400 => 'ERROR',
            300 => 'WARNING',
            250 => 'NOTICE',
            200 => 'INFO',
            100 => 'DEBUG',
        );
        return $_levelMap[$index];
    }
}