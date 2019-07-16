<?php
if ((bool)Mage::getStoreConfig('progos_partialindex/index/disable_process')) {
    class Progos_Partialindex_Model_Index_Resource_Pure extends Progos_Partialindex_Model_Index_Resource_Process { } 
} else {
    class Progos_Partialindex_Model_Index_Resource_Pure extends Mage_Index_Model_Resource_Process { }
}