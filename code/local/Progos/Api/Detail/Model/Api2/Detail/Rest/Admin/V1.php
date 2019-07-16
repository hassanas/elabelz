<?php


class Progos_Api_Detail_Model_Api2_Detail_Rest_Admin_V1 extends Progos_Api_Detail_Model_Api2_Detail
{
    protected $_id;
    protected $_sku;
    protected $cdnUrl;
    protected $cdnUrlFlag;
    protected $mediaUrl;
    protected $baseImageUrl;
    protected $child_images;

    /**
     *
     * @var boolean 
     */
    protected $_isFloat = true;
    
    protected $_currencySmybol;
    
    protected $_product;
    
    protected $_shippingDetails;


    public function __construct() 
    {
        parent::__construct();
        $request = Mage::app()->getRequest();
        $this->setId($request->getParam('id'));
        $this->setSku($request->getParam('sku'));
        $this->setIsFloat($request->getParam('isfloat'));
        $this->setCurrencySymbol(Mage::app()->getLocale()->currency($this->_cuurencyCode)->getSymbol());
    }
    
    public function setId($id)
    {
        $this->_id = $id;
    }
    public function setSku($sku)
    {
        $this->_sku = $sku;
    }
    
    public function getId()
    {
        return $this->_id;
    }

    /**
     * 
     * @param boolean $isFloat
     */
    public function setIsFloat($isFloat = null)
    {
        $this->_isFloat = is_null($isFloat) ? $this->_isFloat : trim($isFloat);
    }
    
    /**
     * 
     * @return boolean
     */
    public function getIsFloat()
    {
        return $this->_isFloat;
    }
    
    /**
     * 
     * @param string $currencySmybol
     */
    public function setCurrencySymbol($currencySmybol)
    {
        $this->_currencySmybol = $currencySmybol;
    }
    
    /**
     * 
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->_currencySmybol;
    }
    
    /**
     * @return array Description
     */
    public function getConfigurableAssociatedProductData()
    {
        $associatedProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $this->_product);
        $a = 0;
        $this->child_images = array();
        $associative_arr = array();
        $name = $this->_product->getName();
        $currencyCode = __($this->_cuurencyCode);
        $currencySymbol = __($this->_currencySmybol);
        $productModel = Mage::getModel('catalog/product');
        foreach ($associatedProducts as $assoc) {
            $assocProduct = $productModel->load($assoc->getId());
            $associative_arr[$a]['sku'] = $assocProduct->getSku();
            $associative_arr[$a]['type_id'] = $assocProduct->getTypeId();
            $associative_arr[$a]['id'] = $assoc->getId();
            $associative_arr[$a]['name'] = $name;
            $associative_arr[$a]['status'] = $assocProduct->getStatus();
            $associative_arr[$a]['currency'] = $currencyCode;
            $associative_arr[$a]['currency_symbol'] = $currencySymbol;
            
            if ($assocProduct->getData('size')) {
                $associative_arr[$a]['size'] = $assocProduct->getAttributeText('size');
            }
            if ($assocProduct->getData('manufacturer')) {
                $associative_arr[$a]['manufacturer'] = $assocProduct->getAttributeText('manufacturer');
            }
            $associative_arr[$a]['price'] = Mage::helper('core')->currency($assocProduct->getPrice(), false, false);
            if ($assocProduct->getSpecialPrice()) {
                $associative_arr[$a]['sale_price'] = Mage::helper('core')->currency($assocProduct->getSpecialPrice(), false, false);
            } else {
                $associative_arr[$a]['sale_price'] = 0;
            }
            
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($assocProduct->getId())->getData();
            $associative_arr[$a]['stock_qty'] = (int)$stock['qty'];
            $associative_arr[$a]['stock_qty_min'] = $stock['min_qty'];
            $associative_arr[$a]['stock_qty_min_sales'] = $stock['min_sale_qty'];
            
            $image = array();
            foreach ($assocProduct->getMediaGalleryImages() as $imagee) {
                if (!in_array($imagee->getUrl(), $image)) {
                    if ($this->cdnUrlFlag){
                        $image[] = str_replace($this->mediaUrl,$this->cdnUrl,$imagee->getUrl());
                    } else {
                        $image[] = $imagee->getUrl();
                    }

                }
            }
            
            
            
            if (empty($image)) {
                $associative_arr[$a]['img'] = array_filter(array($this->baseImageUrl), function ($var) {
                    return !is_null($var);
                });
                $this->child_images[] = $this->baseImageUrl;
            } else {
                $associative_arr[$a]['img'] = array_filter($image, function ($var) {
                    return !is_null($var);
                });
                $this->child_images[] = $image[0];
            }
            
            
            
            
            $attributes = $assocProduct->getAttributes();
            foreach ($attributes as $key => $value) {
                if ($value->getIsVisibleOnFront()) {
                    $val = $value->getFrontend()->getValue($assocProduct);
                    $deniedKeys = array('sizeguidemen', 'shipping_details', 'sizeguidewomen', 'design');
                    if (!in_array($key, $deniedKeys)) {
                        if ($val == null) {
                            $val = "";
                        }
                        $associative_arr[$a][$key] = $val;
                    }
                    if ($key == "shipping_details") {
                        $this->_shippingDetails = $val;
                    }
                }
            }
            
            $a++;
        }
        return $associative_arr;
    }
    
    public function loadProduct()
    {
        if (isset($this->_id)) {
            if (is_numeric($this->_id)) {
                $this->_product = Mage::getModel('catalog/product')->load($this->_id);
            } else {
                $this->_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $this->_id);
            }
        } elseif (isset($this->_sku)){
            $this->_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $this->_sku);
        }
        if(!$this->_product instanceof Mage_Catalog_Model_Product || $this->_product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            throw new Exception('product not found');
        }
    }
    
    /**
     * @ApiDescription(section="Product", description="Get Product Detail Base on Product id/sku")
     * @ApiMethod(type="get")
     * @ApiRoute(name="/product/detail")
     * @ApiParams(name="id", type="mixed", nullable=false, description="product id", sample="{'id/':'1'}")
     * @ApiParams(name="store", type="string", description="store code if store not given then default value is en_ae", nullable=false, sample="{'store':'en_ae'}")
     * @ApiParams(name="isfloat", type="boolean", nullable=true, description="isfloat, default is false", sample="{'isfloat':'true'}")
     * @ApiReturnHeaders(sample="HTTP 200 OK")
     */
    protected function _retrieveCollection()
    {
        try {
            
        Varien_Profiler::start('PRODUCT_DETAIL_API');
        Varien_Profiler::start('PRODUCT_DETAIL_API_loadProduct');
        $this->loadProduct();
        Varien_Profiler::stop('PRODUCT_DETAIL_API_loadProduct');
        Varien_Profiler::start('PRODUCT_DETAIL_API_afterloadProduct');
        $this->cdnUrl = trim(Mage::getStoreConfig('api/emapi/cdn_url'));
        $this->cdnUrlFlag = Mage::getStoreConfigFlag('api/emapi/cdn_url');
        $this->mediaUrl =trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA));
        $productMediaConfig = Mage::getSingleton('catalog/product_media_config');
        if ($this->cdnUrlFlag){
            $this->baseImageUrl = str_replace($this->mediaUrl,$this->cdnUrl, $productMediaConfig->getMediaUrl($this->_product->getImage()));
        } else {
            $this->baseImageUrl = $productMediaConfig->getMediaUrl($this->_product->getImage());
        }
        //get child products associated with this product is

        $custom_options = array();
        
        $image = array();
        foreach ($this->_product->getMediaGalleryImages() as $imagee) {
            if (!in_array($imagee->getUrl(), $image)) {
                if ($this->cdnUrlFlag){
                    $image[] = str_replace($this->mediaUrl,$this->cdnUrl, $imagee->getUrl());
                } else {
                    $image[] = $imagee->getUrl();
                }
            }
        }
        if (empty($image)) {
            $image[] = $this->baseImageUrl;
        }
        $prod['id'] = $this->_product->getId();
        $prod['type_id'] = $this->_product->getTypeId();
        $prod['name'] = $this->_product->getName();
        $prod['sku'] = $this->_product->getSku();
        $prod['description'] = $this->_product->getDescription();
        $prod['status'] = $this->_product->getStatus();
        
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($this->_product->getId());
        $prod['stock_qty'] = (int)$stock->getQty();
        $prod['stock_qty_min'] = $stock->getMinQty();
        $prod['stock_qty_min_sales'] = $stock->getMinSaleQty();
        
        $prod['currency'] = __($this->_cuurencyCode);
        $prod['currency_symbol'] = __($this->_currencySmybol);
        $prod['custom_options'] = $custom_options;
            Varien_Profiler::start('getConfigurableAssociatedProductData');
        $prod['associative'] = $this->getConfigurableAssociatedProductData();
            Varien_Profiler::stop('getConfigurableAssociatedProductData');

        $prod['shipping_details'] = $this->_shippingDetails;

        if($this->_isFloat){
            $prod['sale_price'] = 0;
            $prod['final_price'] = (float)number_format((float)Mage::helper('core')->currency($this->_product->getFinalPrice(), false, false), 2,'.','');
            $prod['price'] = (float)number_format((float)Mage::helper('core')->currency($this->_product->getPrice(), false, false), 2,'.','');

        }else{
            $prod['sale_price'] = 0;
            $prod['final_price'] = ceil(Mage::helper('core')->currency($this->_product->getFinalPrice(), false, false));
            $prod['price'] = ceil(Mage::helper('core')->currency($this->_product->getPrice(), false, false));

        }
        if($prod['price'] != $prod['final_price']){
            $prod['sale_price'] = $prod['final_price'];
        }
        $image = $this->array_random($image, 5);
        $prod['img'] = array_filter($image, function ($var) {
            return !is_null($var);
        });
        
        $prod['manufacturer'] = '';
        if ($this->_product->getAttributeText('manufacturer') != "" && $this->_product->getAttributeText('manufacturer') !== false) {
            $prod['manufacturer'] = $this->_product->getAttributeText('manufacturer');
        }
        
        $prod['material'] = '';
        if (trim($this->_product->getMaterial()) != "" && $this->_product->getMaterial() !== false) {
            $prod['material'] = $this->_product->getMaterial();
        }
        
        $prod['size_shown_in_image'] = '';
        if (trim($this->_product->getSizeShownInImage()) != "" && $this->_product->getSizeShownInImage() !== false) {
            $prod['size_shown_in_image'] = $this->_product->getSizeShownInImage();
        }
        
        $prod['product_type'] = 'configurable';
        
        $configurableOptions = $this->_product->getTypeInstance(true)->getConfigurableAttributesAsArray($this->_product);
        foreach ($configurableOptions as $option_row) {
            $c = 0;
            foreach ($option_row['values'] as $or) {
                $option_row['values'][$c]['color'] = "";
                $option_row['values'][$c]['image'] = $this->child_images[$c];
                $c++;
            }
            $configurable_options[$option_row['attribute_code']] =  array('id' => $option_row['id'], 
                                                                        'attribute_id' => $option_row['attribute_id'],
                                                                        'code' => $option_row['attribute_code'],
                                                                        'label' => $option_row['label'],
                                                                        'values' => $option_row['values']
                                                                    );
        }
        $prod['configurable_options'] = $configurable_options;
            Varien_Profiler::stop('PRODUCT_DETAIL_API_afterloadProduct');
        Varien_Profiler::stop('PRODUCT_DETAIL_API');
        return $prod;
        } catch (Exception $ex) {
            $error = new Varien_Object();
            $error->setData('error', true);
            $error->setData('message', $ex->getMessage());
            return $error->getData();
        }
    }
    
    public function getCollection()
    {
        return $this->_retrieveCollection();
    }
    
    //getting random from an array
    public function array_random($arr, $num = 1)
    {
        shuffle($arr);

        $r = array();
        for ($i = 0; $i < $num; $i++) {
            $r[] = $arr[$i];
        }
        return $num == 1 ? $r[0] : $r;
    }
}