<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Calllog extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Manager overview show increment id and sub order id 
     * 
     * Return the commission percentage
     * @return int
     */
    public function render(Varien_Object $row) {
        
        $orderId = $row->getData($this->getColumn()->getIndex());
        $image = Mage::getDesign()->getSkinBaseUrl(array('_area'=>'adminhtml')).'images/';
        $order = Mage::getModel("sales/order")->load($orderId);

        $ar = unserialize($order->getCallLog());
        // $ar = [];
        $sessions = [];
        $session_times = [];
        $session_times["morning"] = [8,9,10,11];
        $session_times["afternoon"] = [12,13,14,15,16,17];
        $session_times["evening"] = [18,19,20,21,22,23];

        foreach($ar as $ars) {
            $h = (int)date('H', strtotime($ars["timestamp"]));
            $m = (int)date('i', strtotime($ars["timestamp"]));
            
            if (in_array($h, $session_times["morning"]) && $m <= 59) {
                $sessions["morning"][] = $ars;
                $sessions["afternoon"][] = [];
                $sessions["evening"][] = [];
            }
            if (in_array($h, $session_times["afternoon"]) && $m <= 59) {
                $sessions["morning"][] = [];
                $sessions["afternoon"][] = $ars;
                $sessions["evening"][] = [];
            }
            if (in_array($h, $session_times["evening"]) && $m <= 59) {
                $sessions["morning"][] = [];
                $sessions["afternoon"][] = [];
                $sessions["evening"][] = $ars;
            }
        }

        $notry = Mage::getDesign()->getSkinBaseUrl(array('_area'=>'adminhtml')).'images/cancel_icon.gif';
        $failed = Mage::getDesign()->getSkinBaseUrl(array('_area'=>'adminhtml')).'images/error_msg_icon.gif';
        $success = Mage::getDesign()->getSkinBaseUrl(array('_area'=>'adminhtml')).'images/success_msg_icon.gif';
        $html = "
        <div class='calllog'>
            <div class='morning'>
                <center>
        ";
        foreach ($sessions["morning"] as $session) {
            if ($session["status"] === 1) {
                $html .= '<i title="Customer accepted all items at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #8CC442; display: block; cursor: pointer"></i>';
            } elseif ($session["status"] === 0) {
                $html .= '<i title="No ansewr from customer at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #E9C949; display: block; cursor: pointer"></i>';
            } elseif ($session["status"] === 2) {
                $html .= '<i title="Customer asked for replacement at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #59D1F4; display: block; cursor: pointer"></i>';
            } elseif ($session["status"] === 3) {
                $html .= '<i title="Customer number is flagged as wrong number at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #C02020; display: block; cursor: pointer"></i>';
            } else {
                $html .= '<i class="fa fa-phone-square fa-2x" style="color: #D4D4D4; display: block"></i>';
            }
        }
        $html .="
                </center>
            </div>
            <div class='afternoon'>
                <center>
        ";
        foreach ($sessions["afternoon"] as $session) {
            if ($session["status"] === 1) {
                $html .= '<i title="Customer accepted all items at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #8CC442; display: block; cursor: pointer"></i>';
            } elseif ($session["status"] === 0) {
                $html .= '<i title="No ansewr from customer at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #E9C949; display: block; cursor: pointer"></i>';
            } elseif ($session["status"] === 2) {
                $html .= '<i title="Customer asked for replacement at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #59D1F4; display: block; cursor: pointer"></i>';
            } elseif ($session["status"] === 3) {
                $html .= '<i title="Customer number is flagged as wrong number at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #C02020; display: block; cursor: pointer"></i>';
            } else {
                $html .= '<i class="fa fa-phone-square fa-2x" style="color: #D4D4D4; display: block"></i>';
            }
        }
        $html .="
                </center>
            </div>
            <div class='evening'>
                <center>
        ";
        foreach ($sessions["evening"] as $session) {
            if ($session["status"] === 1) {
                $html .= '<i title="Customer accepted all items at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #8CC442; display: block; cursor: pointer"></i>';
            } elseif ($session["status"] === 0) {
                $html .= '<i title="No ansewr from customer at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #E9C949; display: block; cursor: pointer"></i>';
            } elseif ($session["status"] === 2) {
                $html .= '<i title="Customer asked for replacement at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #59D1F4; display: block; cursor: pointer"></i>';
            } elseif ($session["status"] === 3) {
                $html .= '<i title="Customer number is flagged as wrong number at ' . date("jS F, Y h:i A", strtotime($session["timestamp"])) . '" class="fa fa-phone-square fa-2x" style="color: #C02020; display: block; cursor: pointer"></i>';
            } else {
                $html .= '<i class="fa fa-phone-square fa-2x" style="color: #D4D4D4; display: block"></i>';
            }
        }
        $html .= "
                </center>
            </div>
        </div>";

        $url = Mage::helper('adminhtml')->getUrl('marketplaceadmin/adminhtml_callcenter/comments/', array('order_id'=>$orderId));
        $html .= "<div style='clear: both; height: 10px'></div><div onclick='openPopupForm(\"{$url}\",this);' class='pbox-call scalable back'>Customer Call Log</div>";
        $html .= "<div style='margin-top:10px; text-align: left'>";
        if (strlen(trim($ar[sizeof($ar)-1]["comment"]))) {
            $html .= "<div class='order-comment'><strong>Last comment from Call Center</strong>: ";
            $html .= $ar[sizeof($ar)-1]["comment"];
        }
        $html .= "</div></div>";
        return $html;
    }

}

