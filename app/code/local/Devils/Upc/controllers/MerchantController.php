<?php

class Devils_Upc_MerchantController extends Mage_Core_Controller_Front_Action
{
    private $_order;
    /**
     * Session
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if ($this->_order == null) {
            $session = $this->_getSession();
            $this->_order = Mage::getModel('sales/order');
            $this->_order->loadByIncrementId($session->getLastRealOrderId());
        }
        return $this->_order;
    }

    /**
     *
     * Redirect customer to UPC payment interface
     *
     */
    public function redirectAction()
    {
        $session = $this->_getSession();

        $quote_id = $session->getQuoteId();
        $last_real_order_id = $session->getLastRealOrderId();

        if (is_null($quote_id) || is_null($last_real_order_id)) {
            return $this->_redirect('checkout/cart/');
        } else {
            $session->setUpcQuoteId($quote_id);

            $order = $this->_getOrder();
            $order->loadByIncrementId($last_real_order_id);
            if ($order->canInvoice()) {
                $html = $this->getLayout()->createBlock('devils_upc/redirect')->toHtml();
                $this->getResponse()->setHeader('Content-type', 'text/html; charset=utf-8')->setBody($html);
                $order->addStatusHistoryComment(
                    $order->getStatus(),
                    Mage::helper('devils_upc')->__('Customer switch over to UPC payment interface.')
                )->save();

                $session->getQuote()->setIsActive(false)->save();
                $session->setQuoteId(null);
            } else {
                return $this->_redirect('checkout/cart');
            }
        }
    }

    /**
     * When UPC returns customer
     */
    public function successAction()
    {
        $session = $this->_getSession();
        $session->setQuoteId($session->getUpcQuoteId(true));
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();

        $data = $this->getRequest()->getPost();
        Mage::log('failureAction: ' . json_encode($data), null, 'upc.log', true);
        $paymentStatus = Mage::getModel('devils_upc/paymentMethod')->processCallback($data);
        if ($paymentStatus == Devils_Upc_Model_PaymentMethod::PAYMENT_STATUS_SUCCESS) {
            return $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } else {
            return $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    /**
     * When payment fails
     */
    public function failureAction()
    {
        $data = $this->getRequest()->getPost();
        Mage::log('failureAction: ' . json_encode($data), null, 'upc.log', true);
        $paymentStatus = Mage::getModel('devils_upc/paymentMethod')->processCallback($data);
        if ($paymentStatus == Devils_Upc_Model_PaymentMethod::PAYMENT_STATUS_SUCCESS) {
            return $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } else {
            return $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    /**
     * When a customer cancel payment from UPC.
     */
    public function notifyAction()
    {
        $data = $this->getRequest()->getPost();
        if ($data) {
            $model = Mage::getModel('devils_upc/paymentMethod');
            $paymentStatus = $model->processCallback($data);

            $outputKeys = array('MerchantID', 'TerminalID', 'OrderID', 'Currency', 'TotalAmount', 'XID',
                'PurchaseTime', 'Response.action', 'Response.reason', 'Response.forwardUrl');
            $data['Response.action'] = 'reverse';
            $data['Response.reason'] = '';
            $data['Response.forwardUrl'] = Mage::getBaseUrl('upc/merchant/failure');

            if ($paymentStatus) {
                $data['Response.action'] = 'approve';
                $data['Response.forwardUrl'] = Mage::getBaseUrl('upc/merchant/success');
            }

            foreach ($data as $key => $value) {
                if (in_array($key, $outputKeys)) {
                    echo $key . '="' . $value . '"' . "\n";
                }
            }
        }
    }

    public function linkAction()
    {
        $secretId  = $this->getRequest()->getParam('id');
        if (!$secretId) {
            return $this->_redirect('checkout/cart');
        }

        /** @var Devils_Upc_Model_PaymentMethod $model */
        $model = Mage::getModel('devils_upc/paymentMethod');
        $orderId = $model->decodeOrderId($secretId);

        $order = $this->_getOrder();
        $order->loadByIncrementId($orderId);

        if ($order->getId() > 0 && $order->canInvoice()) {
            $session = $this->_getSession();
            $session->setLastRealOrderId($orderId);
            $session->setQuoteId($order->getQuoteId());
            $html = $this->getLayout()->createBlock('devils_upc/redirect')->toHtml();
            $this->getResponse()->setHeader('Content-type', 'text/html; charset=utf-8')->setBody($html);
            $order->addStatusHistoryComment(
                $order->getStatus(),
                Mage::helper('devils_upc')->__('Customer switch over to UPC payment interface.')
            )->save();
        } else {
            return $this->_redirect('checkout/cart');
        }
    }
}