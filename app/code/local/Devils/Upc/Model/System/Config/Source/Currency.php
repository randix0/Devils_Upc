<?php

class Devils_Upc_Model_System_Config_Source_Currency
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 978, 'label' => Mage::helper('devils_upc')->__('Euro')),
            array('value' => 643, 'label' => Mage::helper('devils_upc')->__('Russian Ruble')),
            array('value' => 840, 'label' => Mage::helper('devils_upc')->__('United States Dollar')),
            array('value' => 980, 'label' => Mage::helper('devils_upc')->__('Ukrainian Hryvnia')),
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
            978 => Mage::helper('devils_upc')->__('Euro'),
            643 => Mage::helper('devils_upc')->__('Russian Ruble'),
            840 => Mage::helper('devils_upc')->__('United States Dollar'),
            980 => Mage::helper('devils_upc')->__('Ukrainian Hryvnia'),
        );
    }
}