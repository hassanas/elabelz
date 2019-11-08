<?php 
require_once('../app/Mage.php');
umask(0);
Mage::app();
echo ' msp-and-mageworx-and-highstreet-revert';
echo  Mage::app()->getStore()->getCode();
exit;


        $rolesAndUsers = Mage::getResourceModel('admin/roles_user_collection');
        foreach($rolesAndUsers as $roleUser) {
            $user = Mage::getModel('admin/user')->load($roleUser->getUserId());
            $userRoleData = $user->getRole()->getData();
            if ($userRoleData["role_name"] == "Administrators") {
                $result[$user->getId()] = $user->getName();
            }
        }



exit;
$data = 'a:4:{i:0;a:3:{s:9:"timestamp";s:19:"2016-12-28 18:45:51";s:7:"comment";s:0:"";s:6:"status";i:0;}i:1;a:3:{s:9:"timestamp";s:19:"2016-12-28 18:46:30";s:7:"comment";s:0:"";s:6:"status";i:0;}i:2;a:3:{s:9:"timestamp";s:19:"2016-12-28 19:08:51";s:7:"comment";s:0:"";s:6:"status";i:0;}i:3;a:3:{s:9:"timestamp";s:19:"2016-12-29 08:27:49";s:7:"comment";s:0:"";s:6:"status";i:3;}}';
echo "<pre>";
print_r(unserialize($data));
echo "</pre>";


exit;
  $generate = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'generate.php?content=CR001-H0003-SBCP009-Red-OS';
  $generate_big = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'generate.php?content=CR001-H0003-SBCP009-Red-OS';
    $generate_bigger = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'generate.php?content=CR001-H0003-SBCP009-Red-OS';
?>
<img align="right" style="width: 80%" src="<?php echo $generate ?>">
<img align="right" style="width: 80%" src="<?php echo $generate_big ?>">
<img align="right" style="width: 80%" src="<?php echo $generate_bigger ?>">

<?php 
exit;
$mc = Mage::getModel('marketplace/commission')->getCollection()
->addFieldToSelect ('*')
->addFieldToFilter("is_buyer_confirmation", array("neq"=>"Yes"));

$increment_id = [];

foreach($mc as $mc_row) {
    $increment_id[] = $mc_row->getIncrementId();
}

$increment_id = array_unique($increment_id);


$orders = Mage::getModel("sales/order")->getCollection()
    ->addFieldToSelect ('*')
    ->addFieldToFilter("increment_id", array("in"=>$increment_id))
    ->addFieldToFilter("status", array("eq"=>"pending"))
    ->setOrder('created_at', 'DESC');

$finalIdx = [];
$current_session = getSession(Mage::getModel('core/date')->timestamp());

foreach($orders as $order) {
    $order_session = getSession($order->getCreatedAt());
    $calllog = unserialize($order->getCallLog());

    if (count($calllog) >= 3) {
        continue;
    } elseif (count($calllog) < 3) {
        if ($calllog !== false) {
            $last_call_status = $calllog[count($calllog)-1]["status"];    
        }
        if ($last_call_status == 1) continue;
    }

    if (($order_session == $current_session) && ($order->getSession() == "morning" OR is_null($order->getSession() ) ) ) {
        if (is_null($order->getSession())) {
            $order->setSession("morning");
            $order->save();
        }
        $finalIdx[] = $order->getIncrementId();
    }

    if (($order_session == "Evening") && ($order->getSession() == "evening" OR is_null($order->getSession() ) ) ) {
        if (is_null($order->getSession())) {
            $order->setSession("morning");
            $order->save();
        }
        $finalIdx[] = $order->getIncrementId();
    }
}

$orders = Mage::getModel("sales/order")->getCollection()
    ->addFieldToSelect ('*')
    ->addFieldToFilter("increment_id", array("in"=>$finalIdx))
    ->addFieldToFilter("status", array("eq"=>"pending"))
    ->setOrder('created_at', 'DESC');

foreach ($orders as $order) {
    echo $order->getIncrementId();
}




function getSession($time) {
    $datetime = date("jS F Y H:i:s", Mage::getModel('core/date')->timestamp(strtotime($time)));
    $time = date("h:i A", Mage::getModel('core/date')->timestamp(strtotime($time)));


    $session_times = [];
    $session_times["morning"] = [8,9,10,11];
    $session_times["afternoon"] = [12,13,14,15,16,17];
    $session_times["evening"] = [18,20,21,22,23];

    $h = (int)date('H', strtotime($datetime));
    $m = (int)date('i', strtotime($datetime));
    
    if (in_array($h, $session_times["morning"]) && $m <= 59) {
        return "Morning";
    }
    if (in_array($h, $session_times["afternoon"]) && $m <= 59) {
        return "Afternoon";
    }
    if (in_array($h, $session_times["evening"]) && $m <= 59) {
        return "Evening";
    }

}

exit;

$payments = Mage::getSingleton('payment/config')->getActiveMethods();
$methods = array();
foreach ($payments as $paymentCode=>$paymentModel) {
    $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
    if ($paymentCode == "free") continue;
    $methods[$paymentCode] = array(
        'label'   => $paymentTitle,
        'value' => $paymentCode,
    );
}

echo "<pre>";
print_r($methods);
echo "</pre>";

exit;

$countryList = Mage::getModel('directory/country')->getResourceCollection()->loadByStore()->toOptionArray(true);

echo "<pre>";
print_r($countryList);
echo "</pre>";

exit;
?>
