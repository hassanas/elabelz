<?php
class Progos_Partialindex_Model_Index_Resource_Process extends Mage_Index_Model_Resource_Process
{
    
    /**
     * Register process end
     *
     * @param Mage_Index_Model_Process $process
     * @return Mage_Index_Model_Resource_Process
     */
    public function endProcess(Mage_Index_Model_Process $process)
    {
        $data = array(
            'status'    => Mage_Index_Model_Process::STATUS_PENDING,
            'ended_at'  => $this->formatDate(time()),
        );
        $this->_updateProcessData($process->getId(), $data);
        return $this;
    }

    /**
     * Register process start
     *
     * @param Mage_Index_Model_Process $process
     * @return Mage_Index_Model_Resource_Process
     */
    public function startProcess(Mage_Index_Model_Process $process)
    {
        $data = array(
            'status'        => Mage_Index_Model_Process::STATUS_RUNNING,
            'started_at'    => $this->formatDate(time()),
        );
        $this->_updateProcessData($process->getId(), $data);
        return $this;
    }

    /**
     * Register process fail
     *
     * @param Mage_Index_Model_Process $process
     * @return Mage_Index_Model_Resource_Process
     */
    public function failProcess(Mage_Index_Model_Process $process)
    {
        $data = array(
            'status'   => Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX,
            'ended_at' => $this->formatDate(time()),
        );
        $this->_updateProcessData($process->getId(), $data);
        return $this;
    }

    /**
     * Update process status field
     *
     *
     * @param Mage_Index_Model_Process $process
     * @param string $status
     * @return Mage_Index_Model_Resource_Process
     */
    public function updateStatus($process, $status)
    {
        $data = array('status' => $status);
        $this->_updateProcessData($process->getId(), $data);
        return $this;
    }

    /**
     * Updates process data
     * @param int $processId
     * @param array $data
     * @return Mage_Index_Model_Resource_Process
     */
    protected function _updateProcessData($processId, $data)
    {
        //$bind = array('process_id=?' => $processId);
        //$this->_getWriteAdapter()->update($this->getMainTable(), $data, $bind);
        return $this;
    }

    /**
     * Update process start date
     *
     * @param Mage_Index_Model_Process $process
     * @return Mage_Index_Model_Resource_Process
     */
    public function updateProcessStartDate(Mage_Index_Model_Process $process)
    {
        $this->_updateProcessData($process->getId(), array('started_at' => $this->formatDate(time())));
        return $this;
    }

    /**
     * Update process end date
     *
     * @param Mage_Index_Model_Process $process
     * @return Mage_Index_Model_Resource_Process
     */
    public function updateProcessEndDate(Mage_Index_Model_Process $process)
    {
        $this->_updateProcessData($process->getId(), array('ended_at' => $this->formatDate(time())));
        return $this;
    }

}