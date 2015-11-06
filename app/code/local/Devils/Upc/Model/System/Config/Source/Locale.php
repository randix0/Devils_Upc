<?php

class Devils_Upc_Model_System_Config_Source_Locale
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'en', 'label' => Mage::helper('devils_upc')->__('English')),
            array('value' => 'ru', 'label' => Mage::helper('devils_upc')->__('Russian')),
            array('value' => 'uk', 'label' => Mage::helper('devils_upc')->__('Ukrainian')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'en' => Mage::helper('devils_upc')->__('English'),
            'ru' => Mage::helper('devils_upc')->__('Russian'),
            'uk' => Mage::helper('devils_upc')->__('Ukrainian'),
        );
    }
}