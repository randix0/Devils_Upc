<?php

class Devils_Upc_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('devils/upc/form.phtml');
        parent::_construct();
    }
}