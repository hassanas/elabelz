<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_Extensions_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_cacheKey = null;
    protected $_moduleConfigList = null;
    protected $_moduleList = null;

    public function __()
    {
        $args = func_get_args();
        if ($args[0] == '[SAFEMAGE]') {
            $args[] = $this->getCacheKey();
        }
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), $this->_getModuleName());
        array_unshift($args, $expr);
        return Mage::app()->getTranslator()->translate($args);
    }

    public function getModuleList()
    {
        if (is_null($this->_moduleList)) {
            $moduleList = array();
            foreach ($this->getModuleConfigList() as $moduleCode => $moduleConfig) {
                $moduleList[$moduleCode] = array(
                    'name'    => $moduleConfig->extension_name ? $moduleConfig->extension_name : $moduleCode,
                    'version' => $moduleConfig->version,
                );
            }
            $this->_moduleList = $moduleList;
        }
        return $this->_moduleList;
    }

    public function getModuleConfigList()
    {
        if (is_null($this->_moduleConfigList)) {
            $moduleConfigList = (array) Mage::getConfig()->getNode('modules')->children();
            ksort($moduleConfigList);
            $moduleList = array();
            foreach ($moduleConfigList as $code => $config) {
                if (!$this->_canShowExtension($code, $config)) {
                    continue;
                }
                $moduleList[$code] = $config;
            }
            $this->_moduleConfigList = $moduleList;
        }
        return $this->_moduleConfigList;
    }

    public function getCacheKey()
    {
        if (is_null($this->_cacheKey)) {
            foreach ($this->getModuleConfigList() as $config) {
                $this->_cacheKey .= $config->cache_key;
            }
        }
        return $this->_cacheKey;
    }

    protected function _canShowExtension($code, $config)
    {
        if (!$code || !$config) {
            return false;
        }
        if (!($config instanceof Mage_Core_Model_Config_Element)) {
            return false;
        }
        if (!is_object($config) || !method_exists($config, 'is')) {
            return false;
        }
        if (!$this->_isSafeMageExtension($code)) {
            return false;
        }
        if ($this->_isProtectedExtension($code)) {
            return false;
        }
        return true;
    }

    protected function _isSafeMageExtension($code)
    {
        return (strstr($code,'SafeMage_') !== false);
    }

    protected function _isProtectedExtension($code)
    {
        return $code == 'SafeMage_Extensions';
    }
}
