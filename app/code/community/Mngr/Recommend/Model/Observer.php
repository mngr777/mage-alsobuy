<?php
class Mngr_Recommend_Model_Observer
{
    public function orderSaveLogEvent(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();
        Mage::getSingleton('index/indexer')->logEvent(
            $order,
            Mage_Sales_Model_Order::ENTITY,
            Mage_Index_Model_Event::TYPE_SAVE);
    }

    public function orderSaveIndexEvents(Varien_Event_Observer $observer)
    {
        Mage::getSingleton('index/indexer')->indexEvents(
            Mage_Sales_Model_Order::ENTITY,
            Mage_Index_Model_Event::TYPE_SAVE);
    }
}