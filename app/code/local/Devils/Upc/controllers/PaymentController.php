<?php

class Devils_Upc_PaymentController extends Mage_Core_Controller_Front_Action
{
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
            $this->_redirect('checkout/cart/');
        } else {
            $session->setUpcQuoteId($quote_id);
            $session->setUpcLastRealOrderId($last_real_order_id);

            $order = $this->_getOrder();
            $order->loadByIncrementId($last_real_order_id);

            $html = $this->getLayout()->createBlock('devils_upc/redirect')->toHtml();
            $this->getResponse()->setHeader('Content-type', 'text/html; charset=utf-8')->setBody($html);
/*
            $order->addStatusToHistory(
                $order->getStatus(),
                Mage::helper('devils_upc')->__('Customer switch over to UPC payment interface.')
            )->save();

            $session->getQuote()->setIsActive(false)->save();

            $session->setQuoteId(null);
            $session->setLastRealOrderId(null);
*/
        }
    }
}