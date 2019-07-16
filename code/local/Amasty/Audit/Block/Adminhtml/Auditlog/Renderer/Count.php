<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */


class Amasty_Audit_Block_Adminhtml_Auditlog_Renderer_Count extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /*
     * show username + count of entry
     * */
   public function render(Varien_Object $row)
   {
       $hlp = Mage::helper('amaudit');
       $username = $row->getUsername();
       $count = $row->getCountEntry();
       if ($count > 1) {
           $attempt = $hlp->__(' (%s attempts)', $count);
           $username .= $attempt;
       }
       return $username;
   }
}
