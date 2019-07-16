<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

class SafeMage_Extensions_Model_Etc_Module
{
    public function enable($extensions = array())
    {
        $etcModulesPath = Mage::getBaseDir('app') . DS . 'etc' . DS . 'modules' . DS;
        $newDispatchEvents = array();
        foreach($extensions as $code => $value) {
            $etcModuleFile = $etcModulesPath . $code . '.xml';
            if (!file_exists($etcModuleFile)) {
                continue;
            }

            $etcConfig = simplexml_load_file($etcModuleFile);
            if (!$etcConfig) {
                continue;
            }

            $isEnabled = ($etcConfig->modules->$code->active == 'true' ? 1 : 0);

            if ($isEnabled == $value) {
                continue;
            }

            if (!is_writable($etcModuleFile)) {
                $this->_addWriteError($etcModuleFile);
                continue;
            }

            if ($value) {
                $etcConfig->modules->$code->active = 'true';
            } else {
                $etcConfig->modules->$code->active = 'false';
            }

            $io = new Varien_Io_File();
            if (!$io->write($etcModuleFile, $etcConfig->asXML())) {
                $this->_addWriteError($etcModuleFile);
            }
            $io->close();

            if ($value) {
                $newDispatchEvents[] = 'safemage_extensions_module_' . strtolower($code) . '_enable';
            } else {
                Mage::dispatchEvent('safemage_extensions_module_' . strtolower($code) . '_disable');
            }
        }

        if ($newDispatchEvents) {
            Mage::getSingleton('adminhtml/session')->setNewDispatchEvents($newDispatchEvents);
        }
    }

    private function _addWriteError($etcModuleFile)
    {
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('safemage_extensions')->__(
                'File does not have write permissions: %s', $etcModuleFile
            )
        );
    }
}
