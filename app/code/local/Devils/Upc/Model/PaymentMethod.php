<?php
class Devils_Upc_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    /**
     * Payment Method features
     * @var bool
     */
    protected $_canCapture             = true;
    protected $_canVoid                = true;
    protected $_canUseForMultishipping = false;
    protected $_canUseInternal         = false;
    protected $_isInitializeNeeded     = true;
    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canUseCheckout          = true;

    protected $_code = 'devils_upc';
    protected $_formBlockType = 'devils_upc/form';
    protected $_allowCurrencyCode = array('EUR','UAH','USD','RUB','RUR');
    protected $_order;

    protected function _useSandbox()
    {
        return $this->getConfigData('sandbox');
    }

    public function getUpcPlaceUrl()
    {
        if ($this->_useSandbox()) {
            return $this->getConfigData('sandbox_gateway_url');
        }
        return $this->getConfigData('gateway_url');
    }

    public function getRedirectFormFields()
    {
        return array(
            'foo' => 'fooVal',
            'bar' => 'barVal'
        );
    }

    /**
     * Get redirect url.
     * Return Order place redirect url.
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('upc/payment/redirect', array('_secure' => true));
    }
}