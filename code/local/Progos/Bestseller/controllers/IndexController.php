<?php
/**
 * Progos_Bestseller
 *
 * @category    Progos
 * @package     Progos_Bestseller
 * @author      Touqeer Jalal <touqeer.jalal@progos.org>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */

class Progos_Bestseller_IndexController extends Mage_Core_Controller_Front_Action{
	public function indexAction()
    {		
        $this->loadLayout();
        $this->renderLayout();
    }
}
