<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */



if (Mage::helper('mstcore')->isModuleInstalled('FireGento_PerfectWatermarks') && class_exists('FireGento_PerfectWatermarks_Model_Product_Image')) {
    abstract class Mirasvit_Seo_Model_Rewrite_Product_Image_Abstract extends FireGento_PerfectWatermarks_Model_Product_Image {
    }
} else {
    abstract class Mirasvit_Seo_Model_Rewrite_Product_Image_Abstract extends Mage_Catalog_Model_Product_Image {
    }
}

class Mirasvit_Seo_Model_Rewrite_Product_Image extends Mirasvit_Seo_Model_Rewrite_Product_Image_Abstract
{
	public function setBaseFile($file)
    {
        if (Mage::helper('mstcore')->isModuleInstalled('Extendware_EWImageOpt')
            && Mage::getStoreConfig('ewimageopt/frontend_images/template_image_optimizing_enabled')) {
                parent::setBaseFile($file);
                $fileName = substr(md5($this->_newFile . '-' . $this->_baseFile), 0, 6);
                $md5 = md5($this->_baseFile);
                $newFileName = $this->getUrlKey().'-'.substr($md5, 3, 3);
                $this->_newFile = self::getCacheMediaDir() . DS . dechex(ceil(hexdec($fileName[0].$fileName[1].$fileName[2])/16)) . DS . $fileName[3] . DS . $newFileName . '.' . pathinfo($this->_newFile, PATHINFO_EXTENSION);
                if (self::$checkFilemtime === true) {
                    if (@filemtime($this->_baseFile) >= @filemtime($this->_newFile)) {
                        if (@filemtime($this->_newFile) > 0)  @unlink($this->_newFile);
                    }
                }
                return true;
        }

        $this->_isBaseFilePlaceholder = false;

        if (($file) && (0 !== strpos($file, '/', 0))) {
            $file = '/' . $file;
        }
        $baseDir = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();

        if ('/no_selection' == $file) {
            $file = null;
        }
        if ($file) {
            if ((!file_exists($baseDir . $file)) || !$this->_checkMemory($baseDir . $file)) {
                $file = null;
            }
        }

        if (!$file) {
            // check if placeholder defined in config
            $isConfigPlaceholder = Mage::getStoreConfig("catalog/placeholder/{$this->getDestinationSubdir()}_placeholder");
            $configPlaceholder   = '/placeholder/' . $isConfigPlaceholder;
            if ($isConfigPlaceholder && file_exists($baseDir . $configPlaceholder)) {
                $file = $configPlaceholder;
            }
            else {
                // replace file with skin or default skin placeholder
                $skinBaseDir     = Mage::getDesign()->getSkinBaseDir();
                $skinPlaceholder = "/images/catalog/product/placeholder/{$this->getDestinationSubdir()}.jpg";
                $file = $skinPlaceholder;
                if (file_exists($skinBaseDir . $file)) {
                    $baseDir = $skinBaseDir;
                }
                else {
                    $baseDir = Mage::getDesign()->getSkinBaseDir(array('_theme' => 'default'));
                    if (!file_exists($baseDir . $file)) {
                        $baseDir = Mage::getDesign()->getSkinBaseDir(array('_theme' => 'default', '_package' => 'base'));
                    }
                }
            }
            $this->_isBaseFilePlaceholder = true;
        }

        $baseFile = $baseDir . $file;

        if ((!$file) || (!file_exists($baseFile))) {
            throw new Exception(Mage::helper('catalog')->__('Image file not found'));
        }

        $this->_baseFile = $baseFile;

        // build new filename (most important params)
        $path = array(
            Mage::app()->getStore()->getId(),
            $path[] = $this->getDestinationSubdir()
        );
        if((!empty($this->_width)) || (!empty($this->_height)))
            $path[] = "{$this->_width}x{$this->_height}";

        // add misk params as a hash
        $miscParams = array(
                ($this->_keepAspectRatio  ? '' : 'non') . 'proportional',
                ($this->_keepFrame        ? '' : 'no')  . 'frame',
                ($this->_keepTransparency ? '' : 'no')  . 'transparency',
                ($this->_constrainOnly ? 'do' : 'not')  . 'constrainonly',
                $this->_rgbToString($this->_backgroundColor),
                'angle' . $this->_angle,
                'quality' . $this->_quality
        );

        // if has watermark add watermark params to hash
        if ($this->getWatermarkFile()) {
            $path[] = $this->getWatermarkFile();
            $path[] = $this->getWatermarkImageOpacity();
            $path[] = $this->getWatermarkPosition();
            $path[] = $this->getWatermarkWidth();
            $path[] = $this->getWatermarkHeigth();
        }
        // $crcSum = $this->_crc32_file($this->_baseFile);
        // if ($crcSum) {
        //     $path[] = $crcSum;
        // }
        $path[] = $file;
// pr($path);die;
		$path_info = pathinfo($file);

        $newPath = array();
        $newPath[] = Mage::getBaseDir('media');
        $newPath[] = 'product';
        $newPath[] = substr(md5('/catalog/product'.$file.Mage::app()->getStore()->getId()."{$this->_width}x{$this->_height}"), 0, 3);
        $md5 = md5($this->_baseFile);
        //AdBlock browser extention blocks content with "ad*" in URL
        $md5 = preg_match("/ad[A-Za-z0-9]/i", substr($md5, 3, 3)) ? substr($md5, 4, 3) : substr($md5, 3, 3);
        $newPath[] = $this->getUrlKey().'-'.$md5.'.'.$path_info['extension'];

        $this->_newFile = implode('/', $newPath);

        return $this;
    }

    protected function _rgbToString($rgbArray)
    {
        $result = array();
        foreach ($rgbArray as $value) {
            if (null === $value) {
                $result[] = 'null';
            }
            else {
                $result[] = sprintf('%02s', dechex($value));
            }
        }
        return implode($result);
    }

    protected function _crc32_file($file) {
        return sprintf("%u",crc32(file_get_contents($file)));
    }
}