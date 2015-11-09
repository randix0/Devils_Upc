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

    protected $_transCodes = array(
        '000' => 'SUCCESS',
        '105' => 'Транзакция не разрешена банком-эмитентом',
        '116' => 'Недостаточно средств',
        '111' => 'Несуществующая карта',
        '108' => 'Карта утерена или украдена',
        '101' => 'Неверный срок действия карты',
        '130' => 'Превышен допустимый лимит расходов',
        '290' => 'Банк-издатель не доступен',
        '291' => 'Техническая или коммуникационная ролблема',
        '401' => 'Ошибка формата',
        '402' => 'Ошибка в параметрах Acquirer/Merchant',
        '403' => 'Ошибка при соединении с ресурсом платёжной системы (DS)',
        '404' => 'Ошибка аутентификации покупателя',
        '405' => 'Ошибка подписи',
        '406' => 'Превышена квота разрешонных транзакций',
        '407' => 'Торговец отключён от шлюза',
        '408' => 'Транзакция не найдена',
        '409' => 'Несколько транзакций найдено',
        '410' => 'Заказ уже был успешно оплачен',
        '411' => 'Некорректное время в запросе',
        '412' => 'Параметры заказа уже были получены ранее',
        '420' => 'Превышен дневной лимит транзакций',
        '421' => 'Превышена максимально разрешонная сумма транзакции',
        '430' => 'Транзакция запрещена на уровне платёжного шлюза',
        '431' => 'Не разрешена транзакция без полной аутентификации по схеме 3-D Secure',
        '501' => 'Транзакция отменена пользователем',
        '502' => 'Сессия броузера устарела',
        '503' => 'Транзакция отменена магазином',
        '504' => 'Транзакция отменена шлюзом',
        '601' => 'Транзакция не завершена',
        'default' => 'Неизвестная ошибка'
    );
    const SUCCESS_TRANS_CODE = '000';
    const CERTIFICATES_FOLDER = 'cert';
    const DEFAULT_LOG_FILE = 'payment_upc.log';
    const PAYMENT_STATUS_SUCCESS = 'SUCCESS';
    const PAYMENT_STATUS_FAILURE = 'FAILURE';

    /**
     * PayPal session instance getter
     *
     * @return Mage_Checkout_Model_Session
     */
    private function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _useSandbox()
    {
        return $this->getConfigData('sandbox');
    }

    protected function _isDebugEnabled()
    {
        return $this->getConfigData('debug');
    }

    public function getUpcPlaceUrl()
    {
        if ($this->_useSandbox()) {
            return $this->getConfigData('sandbox_gateway_url');
        }
        return $this->getConfigData('gateway_url');
    }

    /**
     * Get redirect url.
     * Return Order place redirect url.
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('upc/merchant/redirect', array('_secure' => true));
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus(Mage::getSingleton('sales/order_config')->getStateDefaultStatus($state));
        $stateObject->setIsNotified(false);
        return $this;
    }

    public function getRedirectFormFields()
    {
        /** @var Mage_Checkout_Model_Session $session */
        $session = $this->_getCheckoutSession();

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

        $certificatesFolder = Mage::getConfig()->getOptions()->getVarDir() . DS . self::CERTIFICATES_FOLDER . DS;
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

    private function _validateSignature($signatureData, $b64sig)
    {
        $certificatesFolder = Mage::getConfig()->getOptions()->getVarDir() . DS . self::CERTIFICATES_FOLDER . DS;
        $publicKeyPath = $certificatesFolder . $this->getConfigData('upc_cert');
        $fp = fopen($publicKeyPath, "r");
        $publicKey = fread($fp, 8192);
        fclose($fp);
        $publicKeyId = openssl_get_publickey($publicKey);

        $signature = base64_decode($b64sig) ;

        return openssl_verify($signatureData, $signature, $publicKeyId);
    }

    public function processCallback($data = array())
    {
        $paymentStatus = self::PAYMENT_STATUS_FAILURE;

        $MerchantId = $this->getConfigData('merchant_id');
        $TerminalId = $this->getConfigData('terminal_id');

        $PurchaseTime = $data['PurchaseTime'];
        $ProxyPan = $data['ProxyPan'];
        $CurrencyId = $data['Currency'];
        $ApprovalCode = $data['ApprovalCode'];
        $OrderId = $data['OrderID'];
        $b64sig = $data['Signature'];
        $Rrn = $data['Rrn'];
        $XID = $data['XID'];
        $Email = $data['Email'];
        $SD = $data['SD'];
        $TranCode = $data['TranCode'];
        $TotalAmount = $data['TotalAmount'];
        $paidAmount = $TotalAmount/100;
        $signatureData = "$MerchantId;$TerminalId;$PurchaseTime;$OrderId;$XID;$CurrencyId;$TotalAmount;$SD;$TranCode;$ApprovalCode;";

        $isValid = $this->_validateSignature($signatureData, $b64sig);

        unset($publicKey);
        unset($publicKeyId);

        if ($this->_isDebugEnabled()) {
            $logObject = Mage::getModel('core/log_adapter', self::DEFAULT_LOG_FILE);
            $logObject->log(var_export($data, 1));
        }


        if (!$isValid) {
            if ($this->_isDebugEnabled()) {
                $logObject->log("Invalid signature");
            }
            return $paymentStatus;
        }
        if ($this->_isDebugEnabled()) {
            $logObject->log("Callback signature OK");
        }
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($OrderId);

        if (!$order->getId()) {
            if ($this->_isDebugEnabled()) {
                $logObject->log("ERROR: Bad order ID");
            }
            return $paymentStatus;
        }

        switch ($TranCode) {
            case self::SUCCESS_TRANS_CODE:
                if ($order->canInvoice()) {
                    $payment = $order->getPayment();
                    $payment->setTransactionId($XID)
                        ->setIsTransactionClosed(1)
                        ->registerCaptureNotification($paidAmount)
                        ->setStatus(self::STATUS_SUCCESS)
                        ->save();
                    $invoice = $order->prepareInvoice();
                    $invoice->register()->pay();
                    Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();

                    if (!$this->getConfigData('sandbox')) {
                        $message = Mage::helper('devils_upc')
                            ->__('Invoice #%s created.', $invoice->getIncrementId());
                    } else {
                        $message = Mage::helper('devils_upc')
                            ->__('Invoice #%s created (sandbox).', $invoice->getIncrementId());
                    }
                    $order->queueNewOrderEmail();
                    $order->setEmailSent(true);

                    $order->setState(
                        Mage_Sales_Model_Order::STATE_PROCESSING, true,
                        $message,
                        $notified = true
                    );

                    $sDescription = '';
                    $sDescription .= 'amount: ' . $paidAmount . '; ';
                    $sDescription .= 'currency: ' . $CurrencyId . '; ';

                    $order->addStatusHistoryComment($sDescription)
                        ->setIsCustomerNotified($notified);
                    $paymentStatus = self::PAYMENT_STATUS_SUCCESS;
                } else {
                    $order->addStatusHistoryComment(Mage::helper('devils_upc')->__('Error during creation of invoice.'))
                        ->setIsCustomerNotified($notified = true);
                    if ($this->_isDebugEnabled()) {
                        $logObject->log('Error during creation of invoice.');
                    }
                }

                break;
            default:
                $error = $this->__($this->_transCodes[$TranCode]);
                $order->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED, false,
                    $error,
                    $notified = true
                );
                $this->_getCheckoutSession()->addError($error);
                if ($this->_isDebugEnabled()) {
                    $logObject->log($error);
                }
                break;
        }

        $order->save();


        return $paymentStatus;
    }
}