<?php
/**
 * PDF Processor API Class
 *
 * @author		VnEcoms Team <support@vnecoms.com>
 * @website		http://www.vnecoms.com
 */
class VES_AdvancedPdfProcessor_Model_Api
{
    const PDF_PRO_WSDL			= 'r0zxpJlqSHdBKEjlCP9Xng0w/dk2htuUHo0zldCFd4BNGEqzBMVq1Gv2DtTN+RLhZTJU6q8jXyhBhspOkYMw1A==';
    const PDF_PRO_WSDL_DEV		= '/BjMOeoCH8YfyHoPc6j5J+63/2ahVFdeg1v6UIY/1/xFk3sQe6OnDvhTHqetoKYOtwtT9KYsAwU360adBUekdw==';
    const PDF_PRO_XMLRPC		= 'r0zxpJlqSHdBKEjlCP9Xng0w/dk2htuUHo0zldCFd4BkqJyD87fw8QBTsUrwFThlX0xsYPD56yJVLB2n7gKvfw==';
    const PDF_PRO_API_USERNAME	= 'm6oKw/bnKJTI9A+PCkt8nMJMJgQbMxI/sGDQpRxwCbk=';
    const PDF_PRO_API_PASSWORD	= 'wSljQQuWzjU0K8xBeQ159Pb9B/h9P4qeVIZkM+TVz1Q=';
    
    protected $_api_key;
    /**
	 * Decode encoded text
	 * @param string $encoded
	 * @param string $key
	 * @return string
	 */
	protected function _decode($encoded,$key){
		$code = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($encoded), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
		return $code;
	}
	
	/**
	 * Encode text
	 * @param string $code
	 * @param string $key
	 * @return string
	 */
	protected function _encode($code,$key){
		$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $code, MCRYPT_MODE_CBC, md5(md5($key))));
		return $code;
	}
	
	/**
	 * Compress a string
	 * @param string $code
	 * @return string
	 */
	protected function _compress($code){
		return gzdeflate(gzcompress($code,9),9);
	}
	
	/**
	 * Uncompress a string
	 * @param string $code
	 * @return string
	 */
	protected function _unCompress($code){
		return gzuncompress(gzinflate($code));
	}
	
	
	public function __construct($apiKey=null){
    	$this->_api_key = $apiKey;
    	return $this;
    }
    /**
     * Send data to server return content of PDF invoice
     * @param array $data
     * @param string $type
     * @return array
     */
    public function loadInformationFromServer(){
    	if(class_exists('SoapClient')){
	    	$client 			= new PdfProSoapClient($this->_decode(self::PDF_PRO_WSDL, '5e6bf967aab429405f5855145e6e0fa7'));
	    	$client->__setTimeout(1200);
	    	$session 			= $client->login($this->_decode(self::PDF_PRO_API_USERNAME, '5e6bf967aab429405f5855145e6e0fa7'), $this->_decode(self::PDF_PRO_API_PASSWORD, '5e6bf967aab429405f5855145e6e0fa7'));
	    	$result 			= $client->call($session, 'pdfpro.loadInformation',array($this->_encode(Mage::getStoreConfig('pdfpro/pdfprocessor/email'),$this->_api_key), $this->_encode(Mage::getStoreConfig('pdfpro/pdfprocessor/password'),$this->_api_key), $this->_api_key,PdfPro::getVersion()));
	    	$result				= $this->_decode($result, $this->_api_key);
	    	$result = array('success'=>true, 'content'=>$result);
	    	$client->endSession($session);
    	}else if(class_exists('Zend_XmlRpc_Client')){
    		$client 			= new Zend_XmlRpc_Client($this->_decode(self::PDF_PRO_XMLRPC, '5e6bf967aab429405f5855145e6e0fa7'));
    		$client->getHttpClient()->setConfig(array('timeout'=>1200));
    		$session 			= $client->call('login', array($this->_decode(self::PDF_PRO_API_USERNAME, '5e6bf967aab429405f5855145e6e0fa7'), $this->_decode(self::PDF_PRO_API_PASSWORD, '5e6bf967aab429405f5855145e6e0fa7')));
    		$result 			= $client->call('call', array($session, 'pdfpro.loadInformation', array($this->_encode(Mage::getStoreConfig('pdfpro/pdfprocessor/email'),$this->_api_key), $this->_encode(Mage::getStoreConfig('pdfpro/pdfprocessor/password'),$this->_api_key), $this->_api_key,PdfPro::getVersion())));
    		$result				= $this->_decode($result, $this->_api_key);
    		$result 			= array('success'=>true, 'content'=>$result);
    		$client->call('endSession', array($session));
    	}else{
    		$result = array('success'=>false, 'msg'=>"Your website does not support for PDF Pro");
    	}
    	return $result;
    }
}