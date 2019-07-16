<?php
  
class Support_Messaging_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getSession($namespace = "frontend") {
	  Mage::getSingleton('core/session', array('name'=>$namespace));
	  return Mage::getSingleton('customer/session');
	}

	public function getCustomer($customer_id) {
		$customer = $this->getSession("frontend");
		return Mage::getModel('customer/customer')->load($customer_id);
	}

	public function isSeller() {
		$customer = $this->getSession("frontend");
		if ($customer->isLoggedIn()) {
			$customer_group_id         = $customer->getCustomerGroupId ();
			$reseller_group_id      = Mage::helper('marketplace')->getGroupId ();
			$customer_status = $this->getCustomer($customer->getCustomerId())->getCustomerstatus();
			if ($customer_group_id == $reseller_group_id && $customer_status) {
			    return true;
			} else {
				return false;
			}
			return null;
		} else {
			return null;
		}
		return null;
	}

	public function getTime($datetime, $full = false) {
	    $now = new DateTime;
	    $ago = new DateTime($datetime);
	    if (strtotime($datetime)):
		    $diff = $now->diff($ago);

		    $diff->w = floor($diff->d / 7);
		    $diff->d -= $diff->w * 7;

		    $string = array(
		        'y' => 'year',
		        'm' => 'month',
		        'w' => 'week',
		        'd' => 'day',
		        'h' => 'hour',
		        'i' => 'minute',
		        's' => 'second',
		    );
		    foreach ($string as $k => &$v) {
		        if ($diff->$k) {
		            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
		        } else {
		            unset($string[$k]);
		        }
		    }

		    if (!$full) $string = array_slice($string, 0, 1);
		    return $string ? "seen this conversation " . implode(', ', $string) . ' ago' : 'just now';
		else:
			return "never seen this conversation!";
		endif;
		return false;
	}

	public function gtime($datetime, $full = false, $trim = false) {
	    $now = new DateTime;
	    // $now->setTimezone(new DateTimeZone('Asia/Karachi'));

	    $ago = new DateTime($datetime);
	    // $ago->setTimezone(new DateTimeZone('Asia/Karachi'));

	    $diff = $now->diff($ago);

	    $diff->w = floor($diff->d / 7);
	    $diff->d -= $diff->w * 7;

	    $string = array(
	        'y' => 'year',
	        'm' => 'month',
	        'w' => 'week',
	        'd' => 'day',
	        'h' => 'hour',
	        'i' => 'minute',
	        's' => 'second',
	    );
	    foreach ($string as $k => &$v) {
	        if ($diff->$k) {
	            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
	        } else {
	            unset($string[$k]);
	        }
	    }
	    $output = $ago->format("d M");
	    
	    if (($diff->s <= 60) && !$diff->h) {
	    	$output = $diff->s . " seconds ago";
	    }

	    if (($diff->i <= 60 && $diff->i > 0) && !$diff->h) {
	    	$output = $diff->i . " minutes ago";
	    }

	    if (($diff->h > 0) && !$diff->d) {
	    	$output = $ago->format("h:i A");
	    }

	    if (($diff->d == 1) && ($diff->h < 12)) {
	    	$output = "Yesterday, " . $ago->format("h:i A");
	    }

	    if (($diff->d > 1) && !$diff->y) {
	    	$output = $ago->format("d M");
	    }

	    if ($diff->y) {
	    	$output = $ago->format("d M, Y");
	    }

	    return $output;
	}

} 