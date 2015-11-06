<?php

class Devils_Upc_Block_Redirect extends Mage_Core_Block_Template
{
    /**
     * Set template with message
     */
    protected function _construct()
    {
        $this->setTemplate('devils/upc/redirect.phtml');
        parent::_construct();
    }

    /**
     * Return redirect form
     *
     * @return Varien_Data_Form
     */
    public function getForm()
    {
        $paymentMethod = Mage::getModel('devils_upc/paymentMethod');

        //$form = new Form();
        $form = new Varien_Data_Form();
        $form->setAction($paymentMethod->getUpcPlaceUrl())
            ->setId('devils_upc_redirect')
            ->setName('devils_upc_redirect')
            ->setData('accept-charset', 'utf-8')
            ->setUseContainer(true)
            ->setMethod('POST');

        foreach ($paymentMethod->getRedirectFormFields() as $field=>$value) {
            $form->addField($field,'hidden',array('name'=>$field,'value'=>$value));
        }
        return $form;
    }
}