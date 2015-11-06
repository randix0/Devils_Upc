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

    const CERTIFICATES_FOLDER = 'cert';

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
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

        if (!$order->getId()) {
            return array();
        }

        $MerchantId = $this->getConfigData('merchant_id');
        $TerminalId = $this->getConfigData('terminal_id');
        $PurchaseTime = date("ymdHis");
        $OrderId = $order->getIncrementId();
        $CurrencyId = $this->getConfigData('currency_id');
        $TotalAmount = (int)($order->getBaseGrandTotal() * 100);
        $data = "$MerchantId;$TerminalId;$PurchaseTime;$OrderId;$CurrencyId;$TotalAmount;;";

        $certificatesFolder = Mage::getConfig()->getOptions()->getVarDir() . DS . SELF::CERTIFICATES_FOLDER . DS;
        $privateKeyPath = $certificatesFolder . $this->getConfigData('store_pem');
        $fp = fopen($privateKeyPath, "r");
        $privateKey = fread($fp, 8192);
        fclose($fp);
        $privateKeyId = openssl_get_privatekey($privateKey);
        openssl_sign($data, $signature, $privateKeyId);
        openssl_free_key($privateKeyId);
        $b64sign = base64_encode($signature);
        unset($privateKey);
        unset($privateKeyId);

        $formData = array(
            'Version' => '1',
            'MerchantID' => $MerchantId,
            'TerminalID' => $TerminalId,
            'TotalAmount' => $TotalAmount,
            'Currency' => $CurrencyId,
            'locale' => $this->getConfigData('locale'),
            'PurchaseTime' => $PurchaseTime,
            'OrderID' => $OrderId,
            'PurchaseDesc' => 'Заказ №' . $OrderId,
            'Signature' => $b64sign,
        );

        return $formData;
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