<?php
class Infomodus_Dhllabel_Model_Src_Connection_DHLHttpConnection
{
    private $api_url = 'https://xmlpitest-ea.dhl.com/XMLShippingServlet';
    public function __construct()
    {
        if (! function_exists("curl_init")) {
            throw new DHLException("Curl module is not available on this system");
        }
    }

    /**
     * @param string $data
     */
    public function execute($data)
    {
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, utf8_encode($data));
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5 GTB6");

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $exception = new Infomodus_Dhllabel_Model_Src_Exception_DHLConnectionException(curl_error($ch), curl_errno($ch));
            curl_close($ch);
            throw $exception;
        }

        curl_close($ch);

        return $response;
    }

    public function setApiUrl($production=1){
        if($production==0){
            $this->api_url = 'https://xmlpi-ea.dhl.com/XMLShippingServlet';
        }
    }
}
