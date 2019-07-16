<?php
/**
 * @category       Progos
 * @package        Progos_Telrtransparent
 * @copyright      Progos Tech (c) 2018
 * @Author         Hassan Ali Shahzad
 * @date           14/03/2018 11:31
 *
 */

class Progos_Telrtransparent_Model_Checkoutdotcom extends Mage_Core_Model_Abstract
{

    /**
     *
     * @param $order
     * @return mixed
     */
    public function payViaCheckoutDotCom($order)
    {

        $price = (float)$order->getGrandTotal();
        $price = Mage::helper('telrtransparent')->getCheckoutDotComPrice($price, $order->getOrderCurrencyCode());
        $currency = $order->getOrderCurrencyCode();
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        $email = $order->getCustomerEmail();
        $customerName = $billing->getFirstname() . " " . $billing->getLastname();

        $url = Mage::helper('telrtransparent/config')->getCheckoutDotComApiUrl();
        $privateKey = Mage::helper('telrtransparent/config')->getCheckoutDotComPrivateKey($order->getOrderCurrencyCode());
        $ch = curl_init($url);
        $header = array(
            'Content-Type: application/json;charset=UTF-8',
            'Authorization: ' . $privateKey
        );

        $customerIPAddr = Mage::helper('core/http')->getRemoteAddr(false);
        $data_string = '{
          "trackId": "' . $order->getIncrementId() . '",
          "customerIp": "' . $customerIPAddr . '",
          "autoCapture": "Y",
          "autoCapTime": "48",
          "email": "' . $email . '",
          "customerName": "' . $customerName . '",
          "value": "' . $price . '",
          "currency": "' . $currency . '",
		  "chargeMode": 1,
          "cardToken": "' . $_POST['ckoCardToken'] . '",
          "shippingDetails": {
            "addressLine1": "' . $shipping->getStreet(1) . '",
            "addressLine2": "' . $shipping->getStreet(2) . '",
            "postcode": "' . $shipping->getPostcode() . '",
            "country": "' . $shipping->getCountry() . '",
            "city": "' . $shipping->getCity() . '",
            "state": "' . $shipping->getRegion() . '",
            "phone": {
                 "number": "' . $shipping->getTelephone() . '"
             }
          },
          "billingDetails": {
            "addressLine1": "' . $billing->getStreet(1) . '",
            "addressLine2": "' . $billing->getStreet(2) . '",
            "postcode": "' . $billing->getPostcode() . '",
            "country": "' . $billing->getCountry() . '",
            "city": "' . $billing->getCity() . '",
            "state": "' . $billing->getRegion() . '",
            "phone": {
                 "number": "' . $billing->getTelephone() . '"
             }
          }
		  }';

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $output = curl_exec($ch);
        curl_close($ch);

        return json_decode($output, true);
    }
}