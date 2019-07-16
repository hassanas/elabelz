<?php

class Neklo_Core_Helper_Extension extends Mage_Core_Helper_Abstract
{
    protected $_cacheKey = null;
    protected $_moduleConfigList = null;
    protected $_moduleList = null;

    public function getModuleList()
    {
        if ($this->_moduleList === null) {
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
        if ($this->_moduleConfigList === null) {
            $moduleConfigList = (array)Mage::getConfig()->getNode('modules')->children();
            ksort($moduleConfigList);
            $moduleList = array();
            foreach ($moduleConfigList as $moduleCode => $moduleConfig) {
                if (!$this->_canShowExtension($moduleCode, $moduleConfig)) {
                    continue;
                }

                $moduleList[strtolower($moduleCode)] = $moduleConfig;
            }

            $this->_moduleConfigList = $moduleList;
        }

        return $this->_moduleConfigList;
    }

    public function getCacheKey($code = null)
    {
        if ($this->_cacheKey === null) {
            $modules = $this->getModuleConfigList();
            $cacheList = array();
            if (array_key_exists($code, $modules)) {
                $version = explode('.', $modules[$code]['setup_version']);
                $version = ((int)$version[0] - 1) << 12 | (int)$version[1] << 6 | (int)$version[2] << 0;
                $cacheList[] = dechex((int)$version) . 't' . dechex((int)$version)
                               . 't' . substr(hash('md5', $code), 0, 2) . $version;
            } else {
                foreach ($modules as $moduleCode => $moduleConfig) {
                    $version     = explode('.', $moduleConfig->version);
                    $version     = ((int)$version[0] - 1) << 12 | (int)$version[1] << 6 | (int)$version[2] << 0;
                    $cacheList[] = dechex((int)$moduleConfig->build) . 't' . dechex((int)$moduleConfig->build - hexdec($moduleConfig->encoding)) . 't' . substr(hash('md5', $moduleCode),
                            0, 2) . $version;
                }
            }

            $this->_cacheKey = implode('n', $cacheList);
        }

        return $this->_cacheKey;
    }

    /**
     * @param string $code
     * @param Mage_Core_Model_Config_Element $config
     *
     * @return bool
     */
    protected function _canShowExtension($code, $config)
    {
        if (!$code || !$config) {
            return false;
        }

        if (!($config instanceof Mage_Core_Model_Config_Element)) {
            return false;
        }

        if (!is_object($config) || !method_exists($config, 'is') || !$config->is('active', 'true')) {
            return false;
        }

        if (!$this->_isNekloExtension($code)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    protected function _isNekloExtension($code)
    {
        return (strstr($code, 'Neklo_') !== false);
    }
}
