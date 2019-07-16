<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.0.3
 * @revision  314
 * @copyright Copyright (C) 2013 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_Seo_Model_System_Config_Backend_Robot extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
       @file_put_contents($this->getFilename(), utf8_encode($this->getValue()));
    }

    protected function _afterLoad()
    {
        $text = '';
        if (file_exists($this->getFilename())) {
            $text = @file_get_contents($this->getFilename());
        }
        $this->setValue($text);
    }

    protected function getFilename() {
        return Mage::getBaseDir().'/robots.txt';
    }
}
