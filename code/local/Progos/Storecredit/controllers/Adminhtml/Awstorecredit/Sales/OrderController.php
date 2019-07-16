<?php
require_once "AW/Storecredit/controllers/Adminhtml/Awstorecredit/Sales/OrderController.php";

class Progos_Storecredit_Adminhtml_Awstorecredit_Sales_OrderController extends AW_Storecredit_Adminhtml_Awstorecredit_Sales_OrderController
{
    protected function _isAllowed()
    {
        return true;
    }

}
