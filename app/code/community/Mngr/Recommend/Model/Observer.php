<?php
class Mngr_Recommend_Model_Observer
{
    public function reindexOnOrderSave(Varien_Event_Observer $observer)
    {
        $process = Mage::getModel('index/process')->load(
            Mngr_Recommend_Model_Indexer_Similarity::PROCESS_CODE,
            'indexer_code');
        if (!$process->getId()) return;

        try {
            if ($process->getMode() == Mage_Index_Model_Process::MODE_MANUAL) {
                $process->changeStatus(
                    Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
            } else {
                Mage::getSingleton('recommend/indexer_similarity')
                    ->updateOrderProducts($observer->getOrder());
                Mage::helper('recommend')->log('changing status', null, true);
                $process->changeStatus(
                    Mage_Index_Model_Process::STATUS_PENDING);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}