<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Date extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{

    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        $date = date("jS F Y", Mage::getModel('core/date')->timestamp(strtotime($value)));
        $time = date("h:i A", Mage::getModel('core/date')->timestamp(strtotime($value)));

        $sessions["morning"] = [8,9,10,11];
        $sessions["afternoon"] = [12,13,14,15,16,17];
        $sessions["evening"] = [18,20,21,22,23];

        $h = (int)date('H', Mage::getModel('core/date')->timestamp(strtotime($value)));
        $m = (int)date('i', Mage::getModel('core/date')->timestamp(strtotime($value)));
        
        if (in_array($h, $sessions["morning"]) && $m <= 59) {
            $session = "<strong>Morning</strong>";
        }

        if (in_array($h, $sessions["afternoon"]) && $m <= 59) {
            $session = "<strong>Afternoon</strong>";
        }

        if (in_array($h, $sessions["evening"]) && $m <= 59) {
            $session = "<strong>Evening</strong>";
        }

        return $date . " at <strong>" . $time . "</strong> in " . $session . " session.";

        // return date("jS F Y, h:i A", Mage::getModel('core/date')->timestamp(strtotime($value)));
    }

}

