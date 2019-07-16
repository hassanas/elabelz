<?php
class VES_AdvancedPdfProcessor_Model_Mpdf
{
    protected $_api_keys;
    protected $_domains;
    /**
     * Get all api key infor
     * @param string $apiKey
     * @return array $apikeyInfo
     */
    public function getApiKeyInfo($apiKey){
        if(!$this->_api_keys){
            $this->loadInfo();
        }
        $vesHelper = Mage::helper('ves_core');
        if(isset($this->_api_keys[$apiKey])){
            return $this->_api_keys[$apiKey];
        }else{
            $errMsg = Mage::helper('pdfproprocessor')->__('Your API key "%s" is not valid.<br />',$apiKey);
            if(Mage::app()->getLayout()->getArea() == 'adminhtml'){
                throw new Mage_Core_Exception($errMsg);
            }else{
                Mage::log($errMsg, Zend_Log::ERR,'easypdfinvoice.txt');
                throw new Mage_Core_Exception(Mage::helper('pdfproprocessor')->__('Can not generate PDF file. Please contact administrator about this error.'));
            }
        }
    }

    public function checkDomain($domain){
        if(!$this->_domains){
            $this->loadInfo();
        }
        $vesCoreHelper = Mage::helper('ves_core');
        if(!in_array($domain, $this->_domains)){
            $domain1 = trim($domain,'www.');
            if(!in_array($domain1, $this->_domains)){
                if(!in_array('www.'.$domain1, $this->_domains)){
                    $errMsg = Mage::helper('pdfproprocessor')->__("Your domain (%s) hasn't been registed yet<br />Feel free to let me know if you have any question or problem at <a href=\"mailto:support@easypdfinvoice.com\">support@easypdfinvoice.com</a>",$domain);
                    if(Mage::app()->getLayout()->getArea() == 'adminhtml'){
                        throw new Mage_Core_Exception($errMsg);
                    }else{
                        Mage::log($errMsg, Zend_Log::ERR,'easypdfinvoice.txt');
                        throw new Mage_Core_Exception(Mage::helper('pdfproprocessor')->__('Can not generate PDF file. Please contact administrator about this error.'));
                    }
                }
            }
        }
    }

    public function loadInfo(){
        $file	= Mage::getBaseDir('media').DS.'ves_pdfpro'.DS.'pdf.txt';
        $vesHelper = Mage::helper('ves_core');
        if(file_exists($file)){
            $info	= base64_decode(file_get_contents($file));
            $info	= json_decode($info,true);
            /*The data is not valid*/
            if(!is_array($info) || !isset($info['date']) || !isset($info['pdf'])){
                $errMsg = Mage::helper('pdfproprocessor')->__('Please Sync PDF Template first (Easy PDF -> Sync PDF Template).');
                if(Mage::app()->getLayout()->getArea() == 'adminhtml'){
                    throw new Mage_Core_Exception($errMsg);
                }else{
                    Mage::log($errMsg, Zend_Log::ERR,'easypdfinvoice.txt');
                    throw new Mage_Core_Exception(Mage::helper('pdfproprocessor')->__('Can not generate PDF file. Please contact administrator about this error.'));
                }
            }

            $lastUpdate 		= $info['date'];
            $pdfInfo			= json_decode($info['pdf'],true);
            $this->_domains		= $pdfInfo['domains'];
            $this->_api_keys	= $pdfInfo['api_keys'];
        }else{
            $errMsg = Mage::helper('pdfproprocessor')->__('Please Sync PDF Template first (Easy PDF -> Sync PDF Template).');
            if(Mage::app()->getLayout()->getArea() == 'adminhtml'){
                throw new Mage_Core_Exception($errMsg);
            }else{
                Mage::log($errMsg, Zend_Log::ERR,'easypdfinvoice.txt');
                throw new Mage_Core_Exception(Mage::helper('pdfproprocessor')->__('Can not generate PDF file. Please contact administrator about this error.'));
            }
        }
    }

    public function getInfo($apiKey) {
        return Mage::getModel('pdfpro/key')->load($apiKey, 'api_key')->getData();
    }
    /**
     *
     * @param unknown $apiKey	=> api key
     * @param unknown $datas	=> serialize array
     * @param unknown $type		=> type(order, invoice, shipment)
     * @throws Mage_Core_Exception
     * @return multitype:boolean Ambigous <string, NULL>
     */
    public function process($apiKey, $datas, $type){
        //get config tax
        $config = Mage::helper('advancedpdfprocessor')->getTaxDisplayConfig();

        /*Get API Key information*/
        $apiKeyInfo = $this->getInfo($apiKey);		//get info of api (css, template, sku)

        if($type == 'all') return $this->processAllPdf($datas,$apiKey);	//check type of invoice(order,invoice....)
        $vesHelper = Mage::helper('ves_core');
        $sources = array();
        $apiKeys = array();	/*store all api key*/
        foreach($datas as $data){
            $tmpData 	= unserialize($data);

            /*Get API Key information*/
            $pdfInfo 	= $this->getInfo($tmpData['key']);

            if(!is_array($pdfInfo) || !isset($pdfInfo[$type.'_template'])){
                $errMsg = Mage::helper('advancedpdfprocessor')->__('Your API key is not valid.');
                if(Mage::app()->getLayout()->getArea() == 'adminhtml'){
                    throw new Mage_Core_Exception($errMsg);
                }else{
                    Mage::log($errMsg, Zend_Log::ERR,'easypdfinvoice.txt');
                    throw new Mage_Core_Exception(Mage::helper('advancedpdfprocessor')->__('Can not generate PDF file. Please contact administrator about this error.'));
                }
            }

            if(!isset($apiKeys[$tmpData['key']])) $apiKeys[$tmpData['key']] = new Varien_Object($pdfInfo);
            $sources[] = $tmpData;
        }

        $className = Mage::getConfig()->getBlockClassName('advancedpdfprocessor/invoicepro');
        $block = new $className;

        $block->setData(array('config'=>$config, 'source'=>$sources,'type'=>$type,'api_keys'=>$apiKeys))->setArea('adminhtml')->setIsSecureMode(true)->setTemplate('ves_advancedpdfprocessor/template-pro.phtml');

        $config = Mage::helper('advancedpdfprocessor/mconfig');
        $config->loadPdfConfig();

        //var_dump(_MPDF_TTFONTDATAPATH);die();

        $pageSize = Mage::getStoreConfig('pdfpro/advanced/page_size');
        $orientation = Mage::getStoreConfig('pdfpro/advanced/orientation');
        include_once Mage::getBaseDir().'/app/code/community/VES/AdvancedPdfProcessor/Mpdf/mpdf.php';
        $complex_font = Mage::getStoreConfig('pdfpro/advanced/complex_font');

        if(!$complex_font) {
            $mpdf = new mPDF('c');
        }
        else {
            $mpdf = new mPDF('utf-8',$pageSize.'-'.$orientation);
        }

        $mpdf->SetProtection(array('print'));
        $mpdf->SetTitle("VnEcoms");
        $mpdf->SetAuthor("VnEcoms");

        if($complex_font) {
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            //$mpdf->baseScript = 1;
        }
       // $mpdf->watermarkTextAlpha = 0.1;
        //$mpdf->simpleTables = true;

        $mpdf->SetDisplayMode('fullpage');

       // $mpdf->setFooter('{PAGENO}');
        $html = preg_replace('/>\s+</', "><", $block->toHtml()); //echo $html;die();
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        $mpdf->WriteHTML($html);
        //echo $block->getHeader($html);die();
        //$mpdf->SetHTMLHeader($block->getHeader($html));
       // $mpdf->SetHTMLFooter($block->getFooter($html));
        $content = $mpdf->Output('', 'S');

        return array('success'=>true,'content' => $content);
    }

    public function getTemplateName() {
        $orderId = $this->getRequest()->getParam('order_id');
        if (empty($orderId)) {
            Mage::getSingleton('adminhtml/session')->addError('There is no order to process');
            $this->_redirect('adminhtml/sales_order');
            return;
        }
        $order = Mage::getModel('sales/order')->load($orderId);
        if(!$order->getId()){$this->_forward('no-route');return;}
        $orderData= Mage::getModel('pdfpro/order')->initOrderData($order);
        try{
            $result = Mage::helper('pdfpro')->initPdf(array($orderData),'order');
            if($result['success']){
                $this->_prepareDownloadResponse(Mage::helper('pdfpro')->getFileName('order',$order).'.pdf', $result['content']);
            }else{
                throw new Mage_Core_Exception($result['msg']);
            }
        }catch(Exception $e){
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('adminhtml/sales_order/view',array('order_id'=>$orderId));
        }
    }

    /**
     * print all action
     * 2/12/13
     * @param unknown $datas
     * @param unknown $apiKey
     * @throws Mage_Core_Exception
     */
    public function processAllPdf($datas, $apiKey){
        //throw new Mage_Core_Exception(Mage::helper('advancedpdfprocessor')->__('This feature is in development...'));

        //get config tax
        $config = Mage::helper('advancedpdfprocessor')->getTaxDisplayConfig();

        /*Get API Key information*/
        $apiKeyInfo = $this->getInfo($apiKey);		//get info of api (css, template, sku)


        $sources = array();
        $apiKeys = array();	/*store all api key*/
        $count = 0;

        foreach($datas as $sort_order => $data){
            foreach($data as $type => $templates) {
                foreach($templates as $template) {
                    $tmpTemplate = unserialize($template);

                    /*Get API Key information*/
                    $pdfInfo 	= $this->getInfo($tmpTemplate['key']);

                    if(!is_array($pdfInfo) || !isset($pdfInfo[$type.'_template'])){
                        $errMsg = Mage::helper('advancedpdfprocessor')->__('Your API key is not valid.');
                        if(Mage::app()->getLayout()->getArea() == 'adminhtml'){
                            throw new Mage_Core_Exception($errMsg);
                        }else{
                            Mage::log($errMsg, Zend_Log::ERR,'easypdfinvoice.txt');
                            throw new Mage_Core_Exception(Mage::helper('advancedpdfprocessor')->__('Can not generate PDF file. Please contact administrator about this error.'));
                        }
                    }

                    if(!isset($apiKeys[$tmpTemplate['key']])) $apiKeys[$tmpTemplate['key']] = new Varien_Object($pdfInfo);
                    $count++; $sources[$sort_order][$type] = $tmpTemplate;
                }
            }
        }

        $className = Mage::getConfig()->getBlockClassName('advancedpdfprocessor/allpro');
        $block = new $className;

        $block->setData(array('sizeof'=>$count, 'config'=>$config, 'source'=>$sources,'api_keys'=>$apiKeys))->setArea('adminhtml')->setIsSecureMode(true)->setTemplate('ves_advancedpdfprocessor/template-pro-all.phtml');
        if(Mage::getStoreConfig('pdfpro/advanced/minify')){
            define("DOMPDF_ENABLE_FONTSUBSETTING", true);
        }
        define("DOMPDF_TEMP_DIR", Mage::getBaseDir('media').DS.'ves_pdfpro'.DS.'tmp');

        include_once Mage::getBaseDir().'/app/code/community/VES/AdvancedPdfProcessor/Pdf/dompdf_config.inc.php';
        $dompdf = new DOMPDF();
        $html = preg_replace('/>\s+</', "><", $block->toHtml());
        $dompdf->load_html($html);
        $pageSize = Mage::getStoreConfig('pdfpro/advanced/page_size');
        $orientation = Mage::getStoreConfig('pdfpro/advanced/orientation');
        $dompdf->set_paper($pageSize,$orientation);
        $dompdf->render();
        return array('success'=>true,'content' => $dompdf->output());
    }
}
