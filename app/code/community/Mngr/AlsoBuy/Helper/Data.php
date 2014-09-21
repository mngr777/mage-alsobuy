<?php
class Mngr_AlsoBuy_Helper_Data
extends Mage_Core_Helper_Abstract
{
    public function log($message, $level = null, $force = false)
    {
        Mage::log($message, $level, 'alsobuy.log', $force);
    }
}