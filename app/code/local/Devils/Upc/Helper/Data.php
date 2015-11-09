<?php
class Devils_Upc_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getCurrencyCodeById($currencyId)
    {
        $model = Mage::getModel('devils_upc/system_config_source_currency');
        return $model->getCurrencyCodeById($currencyId);
    }
}
