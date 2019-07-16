<?php
class Progos_Ccache_Model_Resource_Ccache extends Mage_Core_Model_Mysql4_Abstract
{
	
    public function _construct()
    {
        $this->_init('ccache/ccache','id');
    }
    
    /**
     * Retrive id by type-id
     *
     * @param int $id
     * @return int|false
     */
    public function getIdByTypeId($id)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from('ccache', 'id')
            ->where('type_id = :type_id');

        $bind = array(':type_id' => (int)$id);

        return $adapter->fetchOne($select, $bind);
    }
    
    /**
     * 
     * @param type $id
     * @param type $type
     * @return int|false
     */
    public function getIdWithTypeAndTypeId($id, $type)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from('ccache', 'id')
            ->where('type_id = :type_id and type = :type');

        $bind = array(':type_id' => (int)$id, ':type' => $type);

        return $adapter->fetchOne($select, $bind);
    }
}