<?php

class Progos_Partialindex_Model_Index_Indexer extends Mage_Index_Model_Indexer 
{
   public function logEvent(Varien_Object $entity, $entityType, $eventType, $doSave=true)
    {
        $event = Mage::getModel('index/event')
            ->setEntity($entityType)
            ->setType($eventType)
            ->setDataObject($entity)
            ->setEntityPk($entity->getId());

        $this->registerEvent($event);
        if ($doSave) {
            //$event->save();
        }
        return $event;
    }
    
    public function processEntityAction(Varien_Object $entity, $entityType, $eventType)
    {
        $event = $this->logEvent($entity, $entityType, $eventType, false);
        /**
         * Index and save event just in case if some process matched it
         */
        if ($event->getProcessIds()) {
            Mage::dispatchEvent('start_process_event' . $this->_getEventTypeName($entityType, $eventType));

            /** @var $resourceModel Mage_Index_Model_Resource_Process */
            $resourceModel = Mage::getResourceSingleton('index/process');

            $allowTableChanges = $this->_allowTableChanges && !$resourceModel->isInTransaction();
            if ($allowTableChanges) {
                $this->_currentEvent = $event;
                $this->_changeKeyStatus(false);
            }

            $resourceModel->beginTransaction();
            $this->_allowTableChanges = false;
            try {
                $this->indexEvent($event);
                $resourceModel->commit();
            } catch (Exception $e) {
                $resourceModel->rollBack();
                if ($allowTableChanges) {
                    $this->_allowTableChanges = true;
                    $this->_changeKeyStatus(true);
                    $this->_currentEvent = null;
                }
                throw $e;
            }
            if ($allowTableChanges) {
                $this->_allowTableChanges = true;
                $this->_changeKeyStatus(true);
                $this->_currentEvent = null;
            }
            //$event->save();
            Mage::dispatchEvent('end_process_event' . $this->_getEventTypeName($entityType, $eventType));
        }
        return $this;
    }
}