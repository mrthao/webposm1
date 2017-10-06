<?php

/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Webpos
 * @copyright   Copyright (c) 2016 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
class Magestore_Webpos_Model_Checkout_Create extends Mage_Adminhtml_Model_Sales_Order_Create
{

    /**
     * @var array
     */
    protected $_quoteInitData = array();

    /**
     * @var string
     */
    protected $_quoteId = '';

    /**
     * @var string
     */
    protected $_config = '';

    /**
     * Magestore_Webpos_Model_Checkout_Create constructor.
     */
    public function __construct()
    {
        $this->_session = Mage::getSingleton('webpos/checkout_session_quote');
        $this->_config = Mage::helper('webpos/config');
    }

    /**
     * @param $quoteId
     */
    public function setQuoteId($quoteId)
    {
        $this->_quoteId = $quoteId;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuoteId()
    {
        return $this->_quoteId;
    }

    /**
     * @var bool
     */
    protected $_isOnline = false;

    /**
     * @return $this
     */
    public function enableOnlineMode()
    {
        $this->_isOnline = true;
        return $this;
    }

    /**
     * @return string
     */
    public function disableOnlineMode()
    {
        $this->_isOnline = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOnline()
    {
        return $this->_isOnline;
    }

    /**
     * @return $this
     */
    public function initSession()
    {
        /**
         * init first billing address, need for virtual products
         */
        $this->getBillingAddress();

        /**
         * Flag for using billing address for shipping
         */
        $this->setShippingAsBilling(1);

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function initData($data = array())
    {
        $this->_quoteInitData = $data;
        if (isset($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::QUOTE_ID]) && isset($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::STORE_ID]) && !empty($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::QUOTE_ID])) {
            $quoteId = $data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::QUOTE_ID];
            $quote = Mage::getModel('sales/quote')->setStoreId($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::STORE_ID])->load($quoteId);
            if (!$quote->getId()) {
                $quote = $this->getQuote();
                $this->initSession();
            }
            $this->setQuoteId($quote->getId());
            $this->setQuote($quote);
        } else {
            $quote = $this->getQuote();
            $this->initSession();
            $this->setQuoteId($quote->getId());
            $this->setQuote($quote);
        }
        $customerId = (!empty($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::CUSTOMER_ID]))
            ? $data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::CUSTOMER_ID]
            : $this->_config->getDefaultCustomerId();
        if ($customerId && $this->getQuote()->getCustomerId() != $customerId) {
            $this->getSession()->setCustomerId((int)$customerId);
            $this->getQuote()->setCustomerId((int)$customerId);
            $customer = Mage::getModel('customer/customer')->load($customerId);
            if ($customer->getId()) {
                $this->getSession()->setCustomer($customer);
                $this->getQuote()->setCustomer($customer);
            }
        }
        if (isset($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::TILL_ID])) {
            $this->getQuote()->setWebposTillId((int)$data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::TILL_ID]);
        }
        if (isset($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::STORE_ID])) {
            $this->getSession()->setStoreId((int)$data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::STORE_ID]);
            $this->getQuote()->setStoreId((int)$data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::STORE_ID]);
        }
        if (isset($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::CURRENCY_ID])) {
            $this->getSession()->setCurrencyId((string)$data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::CURRENCY_ID]);
            $this->setRecollect(true);
        }
        $this->initRuleData();
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuoteInitData()
    {
        $quote = $this->getQuote();
        $this->setQuoteInitData(Magestore_Webpos_Api_Cart_QuoteDataInitInterface::QUOTE_ID, $quote->getId());
        $this->setQuoteInitData(Magestore_Webpos_Api_Cart_QuoteDataInitInterface::CUSTOMER_ID, $quote->getCustomerId());
        return $this->_quoteInitData;
    }

    /**
     * @return mixed
     */
    public function setQuoteInitData($key, $value)
    {
        $data = (is_array($this->_quoteInitData)) ? $this->_quoteInitData : array();
        if (is_array($key)) {
            $data = $key;
        } else {
            $data[$key] = $value;
        }
        $this->_quoteInitData = $data;
        return $this;
    }

    /**
     * Set quote object
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $quote->setIsActive(false)->setIsMultiShipping(false)->save();
        parent::setQuote($quote);
        $this->getSession()->setQuote($quote);
        Mage::dispatchEvent('create_order_session_quote_initialized', array('session_quote' => $this->getSession()));
        return $this;
    }

    /**
     *
     * Retrieve quote object model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        $quote = parent::getQuote();
        if ($quote && $quote->getId()) {
            $this->_quote = $quote;
        } else {
            $quote = $this->getSession()->getQuote();
            $this->setQuote($quote);
            $this->saveQuote();
        }
        return $this->_quote;
    }

    /**
     * Remove Quote
     * @param $data
     */
    public function removeQuote($data)
    {
        if (isset($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::QUOTE_ID]) && isset($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::STORE_ID])) {
            $quoteId = $data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::QUOTE_ID];
            $quote = Mage::getModel('sales/quote')->setStoreId($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::STORE_ID])->load($quoteId);
            if ($quote->getId()) {
                $quote->delete();
            }
        }
        $this->_quote = false;
    }

    /**
     * Retrieve quote item
     *
     * @param   int|Mage_Sales_Model_Quote_Item $item
     * @return  Mage_Sales_Model_Quote_Item
     */
    public function getQuoteItem($item)
    {
        if ($item instanceof Mage_Sales_Model_Quote_Item) {
            return $item;
        } elseif (is_numeric($item)) {
            return $this->getQuote()->getItemById($item);
        }
        return false;
    }

    /**
     * Add multiple products to current order quote
     *
     * @param   array $products
     * @return  Magestore_Webpos_Model_Checkout_Create|Exception
     */
    public function addProducts(array $products)
    {
        foreach ($products as $id => $productConfig) {
            $config['qty'] = isset($productConfig['qty']) ? (float)$productConfig['qty'] : 1;
            try {
                $productId = isset($productConfig[Magestore_Webpos_Api_Cart_BuyRequestInterface::ID]) ? $productConfig[Magestore_Webpos_Api_Cart_BuyRequestInterface::ID] : $id;
                $this->addProduct($productId, $productConfig);
            } catch (Mage_Core_Exception $e) {
                Mage::throwException(
                    Mage::helper('webpos')->__($e->getMessage())
                );
            } catch (Exception $e) {
                Mage::throwException(
                    Mage::helper('webpos')->__($e->getMessage())
                );
            }
        }
        $this->collectRates();
        $this->getQuote()->collectTotals();
        return $this;
    }

    /**
     * @param $product
     * @param int $config
     * @return $this
     */
    public function addProduct($product, $config = 1)
    {
        if (!is_array($config) && !($config instanceof Varien_Object)) {
            $config = array('qty' => $config);
        }
        $config = new Varien_Object($config);
        if (!($product instanceof Mage_Catalog_Model_Product)) {
            $productId = $product;
            $product = Mage::getModel('catalog/product')
                ->setStore($this->getSession()->getStore())
                ->setStoreId($this->getSession()->getStoreId())
                ->load($product);
            if (!$product->getId()) {
                Mage::throwException(
                    Mage::helper('adminhtml')->__('Failed to add a product to cart by id "%s".', $productId)
                );
            }
        }

        $stockItem = $product->getStockItem();
        if ($stockItem && $stockItem->getIsQtyDecimal()) {
            $product->setIsQtyDecimal(1);
        } else {
            $config->setQty((int)$config->getQty());
        }

        $product->setCartQty($config->getQty());
        $item = $this->getQuote()->addProductAdvanced(
            $product,
            $config,
            Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_FULL
        );
        if (is_string($item)) {
            if ($product->getTypeId() != Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE) {
                $item = $this->getQuote()->addProductAdvanced(
                    $product,
                    $config,
                    Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_LITE
                );
            }
            if (is_string($item)) {
                Mage::throwException($item);
            }
        }
        $item->checkData();
        if ($config->getData(Magestore_Webpos_Api_Cart_BuyRequestInterface::CUSTOM_PRICE)) {
            $customPrice = $config->getData(Magestore_Webpos_Api_Cart_BuyRequestInterface::CUSTOM_PRICE);
            $item->setCustomPrice($customPrice);
            $item->setOriginalCustomPrice($customPrice);
            $item->getProduct()->setIsSuperMode(true);
        }
        if ($config->getData(Magestore_Webpos_Api_Cart_BuyRequestInterface::IS_CUSTOM_SALE)) {
            $options = $config->getData(Magestore_Webpos_Api_Cart_BuyRequestInterface::OPTIONS);
            if (isset($options['name'])) {
                $item->setName($options['name']);
            }
            if (isset($options['tax_class_id'])) {
                $item->getProduct()->setTaxClassId($options['tax_class_id']);
            }
        }
        $checkPromotion = $this->getSession()->getData('checking_promotion');
        if ($checkPromotion) {
            $item->setNoDiscount(false);
        } else {
            $item->setNoDiscount(($this->isOnline() || $config->getData('use_discount') == false) ? false : true);
        }
        $this->setRecollect(true);
        return $this;
    }

    /**
     * @param $shippingMethod
     * @return $this
     */
    public function saveShippingMethod($shippingMethod)
    {
        $quote = $this->getQuote();
        if (!$quote->isVirtual() && $this->getShippingAddress()->getSameAsBilling()) {
            $this->setShippingAsBilling(1);
        }
        $this->setShippingMethod($shippingMethod);
        $this->collectShippingRates();
        return $this;
    }

    /**
     * @param $payment
     * @return $this
     */
    protected function _savePaymentData($payment)
    {
        $quote = $this->getQuote();
        $session = $this->getSession();
        $data = array();
        if (isset($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::METHOD])) {
            $data['method'] = $payment[Magestore_Webpos_Api_Checkout_PaymentInterface::METHOD];
        }
        $additional_information = array();
        if (isset($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA]) && count($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA]) > 0) {
            foreach ($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA] as $methodData) {
                $code = $methodData[Magestore_Webpos_Api_Checkout_PaymentItemInterface::CODE];
                $paymentModel = $this->getPaymentModelByCode($code);
                if ($paymentModel) {
                    if (isset($methodData[Magestore_Webpos_Api_Checkout_PaymentItemInterface::ADDITIONAL_DATA]) &&
                        $methodData[Magestore_Webpos_Api_Checkout_PaymentItemInterface::CODE] == 'cryozonic_stripe'
                    ) {
                        $additional_information['token'] = $paymentModel->getPaymentToken(
                            $methodData[Magestore_Webpos_Api_Checkout_PaymentItemInterface::ADDITIONAL_DATA]);
                    }
                }
                $additional_information[] = Mage::app()->getLocale()->currency($session->getCurrencyId())->toCurrency($methodData[Magestore_Webpos_Api_Checkout_PaymentItemInterface::AMOUNT]) . ' : ' . $methodData[Magestore_Webpos_Api_Checkout_PaymentItemInterface::TITLE];
            }
        }
        if (count($additional_information) > 0)
            $data['additional_information'] = $additional_information;

        foreach ($payment as $key => $value){
            if(
                ($key != Magestore_Webpos_Api_Checkout_PaymentInterface::DATA)
                && ($key != Magestore_Webpos_Api_Checkout_PaymentInterface::METHOD)
            ){
                $data[$key] = $value;
            }
        }

        $quote->getPayment()->addData($data);
        return $this;
    }

    /**
     * @param $couponCode
     * @return $this
     */
    protected function _setCouponCode($couponCode)
    {
        $quote = $this->getQuote();
        if ($couponCode) {
            $quote->setCouponCode($couponCode);
        }
        if ($quote->getCouponCode()) {
            $quote->collectTotals();
        }
        return $this;
    }

    /**
     * @param $items
     * @return $this
     */
    protected function _processCart($items)
    {
        if (isset($items) && count($items) > 0) {
            $products = array();
            foreach ($items as $item) {
                $product = array();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::ITEM_ID] = $item->getItemId();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::QTY] = $item->getQty();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::CUSTOM_PRICE] = $item->getCustomPrice();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::USE_DISCOUNT] = $item->getUseDiscount();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::SUPER_ATTRIBUTE] = $item->getSuperAttribute();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::BUNDLE_OPTION] = $item->getBundleOption();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::BUNDLE_OPTION_QTY] = $item->getBundleOptionQty();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::OPTIONS] = $item->getOptions();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::IS_CUSTOM_SALE] = $item->getIsCustomSale();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::EXTENSION_DATA] = $item->getExtensionData();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::CUSTOMERCREDIT_AMOUNT] = $item->getAmount();
                $product[Magestore_Webpos_Api_Cart_BuyRequestInterface::CUSTOMERCREDIT_PRICE_AMOUNT] = $item->getCreditPriceAmount();
                if ($item->getIsCustomSale()) {
                    $options = $item->getOptions();
                    $taxClassId = isset($options['tax_class_id']) ? $options['tax_class_id'] : '';
                    $product = Mage::helper('webpos')->createCustomSaleProduct($taxClassId);
                    if ($product instanceof Mage_Catalog_Model_Product) {
                        $item->setId($product->getId());
                    }
                }
                $products[$item->getId()] = $product;
            }
            $this->addProducts($products);
        }
        return $this;
    }

    /**
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $note
     */
    protected function _saveOrderComment($order, $note)
    {
        if ($order instanceof Mage_Sales_Model_Order) {
            $order->addStatusHistoryComment($note);
        }
    }

    /**
     * @param $order
     * @param $data
     */
    public function saveWebposPayments($order, $data)
    {
        if (is_array($data) && !empty($data)) {
            $totalPaymentAmount = 0;
            $totalBasePaymentAmount = 0;
            $orderId = ($order instanceof Mage_Sales_Model_Order) ? $order->getId() : $order;
            foreach ($data as $payment) {
                if (isset($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::CODE])) {
                    if($this->_config->isEnableCashDrawer()){
                        $tillId = $order->getData('webpos_till_id');
                        if(!$tillId){
                            $helperPermission = Mage::helper('webpos/permission');
                            $sessionString = $helperPermission->getCurrentSession();
                            if($sessionString){
                                $webpossession = Mage::getModel('webpos/user_webpossession');
                                $cashDrawers = $webpossession->getAvailableCashDrawer($sessionString);
                                if(!empty($cashDrawers)){
                                    $tillId = current(array_keys($cashDrawers));
                                    $tillId = ($tillId)?$tillId:0;
                                    $order->setData('webpos_till_id', $tillId);
                                }
                            }
                        }
                    }
                    if(($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::CODE] == Magestore_Webpos_Helper_Payment::CASH_PAYMENT_CODE) && $this->_config->isEnableCashDrawer()){
                        $transaction = Mage::getModel('webpos/transaction');
                        $transaction->setData(array(
                            Magestore_Webpos_Api_TransactionInterface::STAFF_ID => $order->getData('webpos_staff_id'),
                            Magestore_Webpos_Api_TransactionInterface::TILL_ID => $order->getData('webpos_till_id'),
                            Magestore_Webpos_Api_TransactionInterface::ORDER_INCREMENT_ID => $order->getData('increment_id'),
                            Magestore_Webpos_Api_TransactionInterface::TRANSACTION_CURRENCY_CODE => $order->getData('order_currency_code'),
                            Magestore_Webpos_Api_TransactionInterface::BASE_CURRENCY_CODE => $order->getData('base_currency_code'),
                            Magestore_Webpos_Api_TransactionInterface::AMOUNT => $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::REAL_AMOUNT],
                            Magestore_Webpos_Api_TransactionInterface::BASE_AMOUNT => $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::BASE_REAL_AMOUNT],
                            Magestore_Webpos_Api_TransactionInterface::NOTE => ''
                        ));
                        $transaction->save();
                    }

                    $orderPayment = Mage::getModel('webpos/payment_orderPayment');
                    $orderPayment->setData(array(
                        "order_id" => $orderId,
                        "real_amount" => ($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::IS_PAYLATER]) ? 0 : $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::REAL_AMOUNT],
                        "base_real_amount" => ($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::IS_PAYLATER]) ? 0 : $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::BASE_REAL_AMOUNT],
                        "payment_amount" => ($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::IS_PAYLATER]) ? 0 : $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::AMOUNT],
                        "base_payment_amount" => ($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::IS_PAYLATER]) ? 0 : $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::BASE_AMOUNT],
                        "method" => $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::CODE],
                        "method_title" => $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::TITLE],
                        "till_id" => $order->getData('webpos_till_id'),
                        "reference_number" => (!empty($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::REFERENCE_NUMBER])) ? $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::REFERENCE_NUMBER] : ''
                    ));
                    $orderPayment->save();

                    $totalPaymentAmount += $orderPayment->getPaymentAmount();
                    $totalBasePaymentAmount += $orderPayment->getBasePaymentAmount();
                }
            }
            $order = ($order instanceof Mage_Sales_Model_Order) ? $order : Mage::getModel('saes/order')->load($order);
            if (($order->getBaseGrandTotal() < $totalBasePaymentAmount) && !$order->getWebposBaseChange()) {
                $order->setWebposBaseChange($totalBasePaymentAmount - $order->getBaseGrandTotal());
                $order->setWebposChange($totalPaymentAmount - $order->getGrandTotal());
            }
        }
    }

    /**
     *
     * @param array $data
     * @return array $paidPayment
     */
    protected function _getPaidPayment($data)
    {
        $paidPayment = array(
            'amount' => 0,
            'base_amount' => 0
        );
        if (count($data) > 0) {
            $amount = $base_amount = 0;
            foreach ($data as $payment) {
                if (isset($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::CODE])) {
                    $amount += ($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::IS_PAYLATER]) ? 0 : $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::AMOUNT];
                    $base_amount += ($payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::IS_PAYLATER]) ? 0 : $payment[Magestore_Webpos_Api_Checkout_PaymentItemInterface::BASE_AMOUNT];
                }
            }
            $paidPayment['amount'] = $amount;
            $paidPayment['base_amount'] = $base_amount;
        }
        return $paidPayment;
    }

    /**
     *
     * @return array
     */
    public function getTotals()
    {
        $quote = $this->getQuote();
        $quoteTotals = $quote->getTotals();
        $totals = array();
        foreach ($quoteTotals as $code => $total) {
            $totals[$code] = $total->getData();
        }
        return $totals;
    }

    public function prepareOrder($customerId, $items, $payment, $shipping, $config, $couponCode = "", $extensionData, $sessionData, $integration)
    {
        $session = $this->getSession();
        $session->clear();
        $order = $this->createOrderByParams($session, $customerId, $items, $payment, $shipping, $config,
            $couponCode, $extensionData, $sessionData, $integration);
        if ($order) {
            $createInvoice = $config->getCreateInvoice();
            $createShipment = $config->getCreateShipment();
            $paidPayment = array(
                'amount' => 0,
                'base_amount' => 0
            );
            if (isset($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA])) {
                $paidPayment = $this->_getPaidPayment($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA]);
            }
            if ($config->getNote()) {
                $this->_saveOrderComment($order, $config->getNote());
            }
            if ($shipping->getDateTime()) {
                $order->setData('webpos_delivery_date', $shipping->getDateTime());
            }

            $order->setData('total_paid', $paidPayment['amount']);
            $order->setData('base_total_paid', $paidPayment['base_amount']);
            $order->save();
            if (isset($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA])) {
                $this->saveWebposPayments($order, $payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA]);
            }
            if ($createInvoice) {
                $this->createInvoice($order->getIncrementId());
            }
            if ($createShipment) {
                $this->createShipment($order->getIncrementId());
            }
            $order->save();
            Mage::dispatchEvent('webpos_order_sync_after', array('order' => $order));
        } else {
            Mage::throwException(
                Mage::helper('adminhtml')->__('Cannot create order!')
            );
        }
        try {
            if (Mage::helper('webpos/config')->isEnableAutoSendEmail('order')) {
                $this->sendEmail($order);
            }
        } catch (Exception $e) {
            return $order;
        }
        return $order;
    }


    /**
     *
     * @param string $customerId
     * @param \Magestore\Webpos\Api\Data\CartItemInterface[] $items
     * @param \Magestore\Webpos\Api\Data\Checkout\PaymentInterface $payment
     * @param \Magestore\Webpos\Api\Data\Checkout\ShippingInterface $shipping
     * @param \Magestore\Webpos\Api\Data\Checkout\ConfigInterface $config
     * @param string $couponCode
     * @param \Magestore\Webpos\Api\Data\Checkout\ExtensionDataInterface[] $extensionData
     * @param \Magestore\Webpos\Api\Data\Checkout\SessionDataInterface[] $sessionData
     * @param \Magestore\Webpos\Api\Data\Checkout\IntegrationDataInterface[] $integration
     * @return \Magestore\Webpos\Api\Data\Sales\OrderInterface
     * @throws \Exception
     */
    public function createOrderByParams($webposSession, $customerId, $items, $payment, $shipping, $config, $couponCode = "", $extensionData, $sessionData, $integration)
    {
        $this->_addSessionData($sessionData);

        /* Create Order when customer not sync */
        if ($customerId) {
            if (strpos($customerId, 'notsync') !== false) {
                $split = explode('_', $customerId);
                if (isset($split[1])) {
                    $customerEmail = $split[1];
                    $customerModel = Mage::getModel('customer/resource_customer_collection')
                        ->addFieldToFilter('email', $customerEmail)
                        ->getFirstItem();
                    if ($customerModel->getId()) {
                        $customerId = $customerModel->getId();
                    }
                }
            }
        }
        /* End */
        $syncedOrder = $this->getSyncOrder($extensionData);
        if ($syncedOrder !== false) {
            return Mage::getModel('sales/order')->load($syncedOrder);
        }
        $store = Mage::app()->getStore();
        $storeId = $store->getId();
        $webposSession->setCurrencyId($config->getCurrencyCode());
        $webposSession->setStoreId($storeId);
        $webposSession->setData('checking_promotion', false);
        $webposSession->setData('webpos_order', 1);
        $store->setCurrentCurrencyCode($config->getCurrencyCode());
        $this->getQuote()->setQuoteCurrencyCode($config->getCurrencyCode());
        $storeAddress = $this->getStoreAddressData();
        if ($customerId) {
            $customer = Mage::getModel('customer/customer');
            $customer->load($customerId);
            if ($customer->getId()) {
                $webposSession->setCustomerId($customerId);
                $this->getQuote()->setCustomer($customer);
            }
        } else {
            $this->getQuote()->setCustomerIsGuest(true);
            $this->getQuote()->setCustomerEmail($storeAddress['email']);
        }
        $this->_processCart($items);
        $this->setWebPosBillingAddress($payment, $storeAddress);
        if (!$this->getQuote()->isVirtual()) {
            $this->setWebPosShippingAddress($shipping, $storeAddress);
            $this->saveShippingMethod($shipping->getMethod());
        }
        $this->_savePaymentData($payment);
        $this->_setCouponCode($couponCode);
        $this->getQuote()->getShippingAddress()->unsCachedItemsAll();
        $this->getQuote()->setTotalsCollectedFlag(false);
        $this->saveQuote();
        if (!Mage::helper('webpos/config')->isEnableAutoSendEmail('order')) {
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, "0");
            $this->setSendConfirmation(false);
        }
        $template_order = Mage::helper('webpos/config')->getWebposEmailTemplate('order');
        if (isset($template_order['guest']) && $template_order['guest'] != '') {
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_GUEST_TEMPLATE, $template_order['guest']);
        }
        if (isset($template_order['customer']) && $template_order['customer'] != '') {
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_TEMPLATE, $template_order['customer']);
        }
        $order = $this->createOrder();
        $this->_removeSessionData($sessionData);

        if ($order) {
            $items = $order->getAllVisibleItems();
            if (count($items) > 0) {
                foreach ($items as $item) {
                    $buyRequest = $item->getBuyRequest()->getData();
                    if (isset($buyRequest[Magestore_Webpos_Api_Cart_BuyRequestInterface::EXTENSION_DATA])) {
                        foreach ($buyRequest[Magestore_Webpos_Api_Cart_BuyRequestInterface::EXTENSION_DATA] as $data) {
                            $item->setData($data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_KEY], $data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_VALUE]);
                        }
                    }
                }
            }
            if (count($extensionData) > 0) {
                foreach ($extensionData as $data) {
                    $order->setData($data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_KEY], $data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_VALUE]);
                    if ($data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_KEY] == "webpos_order_id") {
                        $order->setData("increment_id", $data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_VALUE]);
                    }
                }
            }
            if ($config->getCartDiscountAmount()) {
                $order->setData('discount_amount', $config->getCartDiscountAmount());
                $order->setData('base_discount_amount', $config->getCartBaseDiscountAmount());
                $order->setData('discount_description', $config->getCartDiscountName());

                if ($couponCode) {
                    $order->setData('discount_description', $couponCode);
                    $order->setData('coupon_code', $couponCode);
                }
                if (count($items) > 0) {
                    $baseDiscountAmount = -$config->getCartBaseDiscountAmount();
                    $discountAmount = -$config->getCartDiscountAmount();
                    $subtotal = $order->getData('subtotal');
                    $baseSubtotal = $order->getData('base_subtotal');
                    $tax = $order->getData('tax_amount');
                    $baseTax = $order->getData('base_tax_amount');
                    if ($baseDiscountAmount > $baseSubtotal) {
                        $baseShippingDiscount = $baseDiscountAmount - $baseSubtotal - $baseTax;
                        $shippingDiscount = $discountAmount - $subtotal - $tax;
                        $order->setData('shipping_discount_amount', $shippingDiscount);
                        $order->setData('base_shipping_discount_amount', $baseShippingDiscount);
                    }
                }
            }
            $order = $this->processIntegration($order, $integration, false);
        }

        return $order;
    }

    /**
     *
     * @param string $code
     *
     * @return model
     */
    public function getPaymentModelByCode($code)
    {
//        $paymentMethodNames = explode('_', $code);
//        $paymentModelName = '';
//        foreach ($paymentMethodNames as $name) {
//            $paymentModelName .= '\\'.ucfirst($name);
//        };
//        $paymentModelName = 'Magestore\Webpos\Model\Payment\Online'.$paymentModelName;
//        if(class_exists($paymentModelName)) {
//            $paymentModel = $this->_objectManager->create($paymentModelName);
//            return $paymentModel;
//        }
        return false;
    }

    /**
     * get sync order
     *
     * @param array
     *
     * @return array
     */
    public function getSyncOrder($extensionData)
    {
        $syncedOrder = false;
        if (count($extensionData) > 0) {
            foreach ($extensionData as $data) {
                if ($data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_KEY] == "webpos_order_id") {
                    $orderModel = Mage::getModel('sales/order');
                    $orderModel = $orderModel->loadByIncrementId($data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_VALUE]);
                    if ($orderModel->getEntityId()) {
                        $syncedOrder = $orderModel->getEntityId();
                    }
                }
            }
        }
        return $syncedOrder;
    }

    /**
     * set billing address
     *
     * @param array , array
     *
     * @return void
     */
    public function setWebPosBillingAddress($payment, $storeAddress)
    {
        $address = $payment->getAddress();
        if (!empty($address)) {
            $billingData = $address->getData();
            if (empty($billingData['id']) || strpos($billingData['id'], "nsync") !== false) {
                unset($billingData['id']);
            }
            $billingData['saveInAddressBook'] = false;
            $this->setBillingAddress($billingData);
        } else {
            $this->setBillingAddress($storeAddress);
        }
    }

    /**
     * set shipping address
     *
     * @param array , array
     *
     * @return void
     */
    public function setWebPosShippingAddress($shipping, $storeAddress)
    {
        $address = $shipping->getAddress();
        if (!empty($address)) {
            $shippingData = $address->getData();
            if (empty($shippingData['id']) || strpos($shippingData['id'], "nsync") !== false) {
                unset($shippingData['id']);
            }
            $shippingData['saveInAddressBook'] = false;
            $this->setShippingAddress($shippingData);
        } else {
            $this->setShippingAddress($storeAddress);
        }
    }

    /**
     * @param $sessionData
     */
    protected function _addSessionData($sessionData)
    {
        if (count($sessionData) > 0) {
            $session = $this->getSession();
            foreach ($sessionData as $data) {
                if (isset($data[Magestore_Webpos_Api_Checkout_SessionDataInterface::SESSION_CLASS])) {
                    $sessionClass = $data[Magestore_Webpos_Api_Checkout_SessionDataInterface::SESSION_CLASS];
                    $model = Mage::getSingleton($sessionClass);
                    $model->setData($data[Magestore_Webpos_Api_Checkout_SessionDataInterface::FIELD_KEY], $data[Magestore_Webpos_Api_Checkout_SessionDataInterface::FIELD_VALUE]);
                } else {
                    $session->setData($data[Magestore_Webpos_Api_Checkout_SessionDataInterface::FIELD_KEY], $data[Magestore_Webpos_Api_Checkout_SessionDataInterface::FIELD_VALUE]);
                }
            }
        }
    }

    /**
     * @param $sessionData
     */
    protected function _removeSessionData($sessionData)
    {
        if (count($sessionData) > 0) {
            $session = $this->getSession();
            foreach ($sessionData as $data) {
                if (isset($data[Magestore_Webpos_Api_Checkout_SessionDataInterface::SESSION_CLASS])) {
                    $sessionClass = $data[Magestore_Webpos_Api_Checkout_SessionDataInterface::SESSION_CLASS];
                    $model = Mage::getSingleton($sessionClass);
                    $model->setData($data[Magestore_Webpos_Api_Checkout_SessionDataInterface::FIELD_KEY], null);
                } else {
                    $session->setData($data[Magestore_Webpos_Api_Checkout_SessionDataInterface::FIELD_KEY], null);
                }
            }
        }
    }

    /**
     * @param $order
     * @param $integration
     * @param bool $saved
     * @return mixed
     */
    public function processIntegration($order, $integration, $saved = true)
    {
        if (count($integration) > 0) {
            if($saved){
                $order = Mage::getModel('sales/order')->loadByIncrementId($order->getIncrementId());
            }
            foreach ($integration as $extension) {
                $datas = $extension->getOrderData();
                $extensionData = $extension->getExtensionData();
                $eventName = $extension->getEventName();
                $eventData = array(
                    'order' => $order,
                    'order_data' => array(),
                    'extension_data' => array()
                );
                if (count($datas) > 0) {
                    foreach ($datas as $data) {
                        $order->setData($data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_KEY], $data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_VALUE]);
                        $eventData['order_data'][$data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_KEY]] = $data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_VALUE];
                    }
                }
                if (count($extensionData) > 0) {
                    foreach ($extensionData as $data) {
                        $eventData['extension_data'][$data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_KEY]] = $data[Magestore_Webpos_Api_Checkout_ExtensionDataInterface::FIELD_VALUE];
                    }
                }
                Mage::dispatchEvent($eventName, $eventData);
            }
            $order->save();
        }
        return $order;
    }

    /**
     *
     * @return array
     */
    public function getStoreAddressData()
    {
        return Mage::helper('webpos/config')->getWebposStoreAddress();
    }

    public function useDefaultAddresses($type)
    {
        $quote = $this->getQuote();
        $addressData = $this->getStoreAddressData();
        $address = Mage::getModel("sales/quote_address");
        $address->setData($addressData);

        $address->implodeStreetAddress();

        switch ($type) {
            case 'billing':
                $address->setEmail($quote->getCustomer()->getEmail());

                if (!$quote->isVirtual()) {
                    $billingAddress = clone $address;
                    $billingAddress->unsAddressId()->unsAddressType();

                    $shippingAddress = $quote->getShippingAddress();
                    $shippingMethod = $shippingAddress->getShippingMethod();
                    $shippingAddress->addData($billingAddress->getData())
                        ->setSameAsBilling(1)
                        ->setShippingMethod($shippingMethod)
                        ->setCollectShippingRates(true);
                }
                $quote->setBillingAddress($address);
                break;

            case 'shipping':
                $address->setCollectShippingRates(true)->setSameAsBilling(0);
                $quote->setShippingAddress($address);
                break;
        }

        try {
            $quote->collectTotals();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return true;
    }

    /**
     * @param $data
     * @return $this
     */
    public function addQuoteData($data)
    {
        if (!empty($data)) {
            $quote = $this->getQuote();
            $quote->addData($data);
        }
        return $this;
    }

    /**
     * @param $customerId
     * @param $items
     * @param $payment
     * @param $shipping
     * @param $config
     * @param string $couponCode
     * @return array
     */
    public function checkPromotion($customerId, $items, $payment, $shipping, $config, $couponCode = "")
    {
        $session = $this->getSession();
        $session->clear();
        $session->setCurrencyId($config->getCurrencyCode());
        $session->setData('checking_promotion', true);

        $storeAddress = $this->getStoreAddressData();
        if ($customerId) {
            $customer = Mage::getModel('customer/customer')->load($customerId);
            if ($customer->getId()) {
                $session->setCustomerId($customerId);
                $this->getQuote()->setCustomer($customer);
            }
        } else {
            $this->getQuote()->setCustomerIsGuest(true);
            $this->getQuote()->setCustomerEmail($storeAddress['email']);
        }
        $this->setWebPosBillingAddress($payment, $storeAddress);

        $this->initRuleData();
        $this->_processCart($items);
        $this->setWebPosBillingAddress($payment, $storeAddress);
        if (!$this->getQuote()->isVirtual()) {
            $this->setWebPosShippingAddress($shipping, $storeAddress);
            $this->saveShippingMethod($shipping->getMethod());
        }
        $this->_savePaymentData($payment);
        $this->_setCouponCode($couponCode);
        $this->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
        $discountTotal = 0;
        $baseDiscountTotal = 0;
        $quote = $this->getQuote();
        foreach ($quote->getAllItems() as $item) {
            $discountTotal += $item->getDiscountAmount();
            $baseDiscountTotal += $item->getBaseDiscountAmount();
        }
        $response = array(
            "discount_amount" => $discountTotal,
            "base_discount_amount" => $baseDiscountTotal
        );
        $this->_removeCurrentQuote();
        return $response;
    }

    /**
     *
     * @param string $incrementId
     * @param string $email
     * @return string
     */
    public function sendEmail($incrementId, $email = '')
    {
        $response = array();
        if ($incrementId) {
            $template_order = Mage::helper('webpos/config')->getWebposEmailTemplate('order');
            if (isset($template_order['guest']) && $template_order['guest'] != '') {
                Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_GUEST_TEMPLATE, $template_order['guest']);
            }
            if (isset($template_order['customer']) && $template_order['customer'] != '') {
                Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_TEMPLATE, $template_order['customer']);
            }
            $order = ($incrementId instanceof Mage_Sales_Model_Order) ? $incrementId : Mage::getModel('sales/order')->loadByIncrementId($incrementId);
            $order->setEmailSent(false);
            if ($email != '') {
                $order->setCustomerEmail($email);
            }
            $order->sendNewOrderEmail();
            if ($order && $order->getId()) {
                $error = false;
                $message = Mage::helper('adminhtml')->__('The order #%s has been sent to the customer %s',
                    $order->getIncrementId(), $order->getCustomerEmail());
            } else {
                $error = true;
                $message = Mage::helper('adminhtml')->__('The order #%s cannot be sent to the customer %s',
                    $order->getIncrementId(), $order->getCustomerEmail());
            }

        } else {
            $error = true;
            $message = Mage::helper('adminhtml')->__('Cannot send the order');
        }
        $response['error'] = $error;
        $response['message'] = $message;
        return $response;
    }

    /**
     * @param $order
     * @param $payment
     */
    public function processPaymentAfterCreateOrder($order, $payment)
    {
        if ($order instanceof Mage_Sales_Model_Order && is_array($payment) && !empty($payment)) {
            if (isset($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::METHOD])) {
                $this->saveWebposPayments($order, $payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA]);
            }
            $paidPayment = array(
                'amount' => 0,
                'base_amount' => 0
            );
            if (isset($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA])) {
                $paidPayment = $this->_getPaidPayment($payment[Magestore_Webpos_Api_Checkout_PaymentInterface::DATA]);
            }
            $order->setData('total_paid', $paidPayment['amount']);
            $order->setData('base_total_paid', $paidPayment['base_amount']);
            $order->save();
        }
    }

    /**
     * @param $order
     * @param $actions
     */
    public function processActionsAfterCreateOrder($order, $actions)
    {
        if ($order instanceof Mage_Sales_Model_Order && is_array($actions) && !empty($actions)) {
            if (isset($actions[Magestore_Webpos_Api_Checkout_ConfigInterface::KEY_CREATE_INVOICE]) && $actions[Magestore_Webpos_Api_Checkout_ConfigInterface::KEY_CREATE_INVOICE] == true) {
                $this->createInvoice($order->getIncrementId());
            }
            if (isset($actions[Magestore_Webpos_Api_Checkout_ConfigInterface::KEY_CREATE_SHIPMENT]) && $actions[Magestore_Webpos_Api_Checkout_ConfigInterface::KEY_CREATE_SHIPMENT] == true) {
                $this->createShipment($order->getIncrementId());
            }
        }
    }

    /**
     * Create invoice
     * @param $orderIncrementId
     * @param $itemsQty
     * @param null $comment
     * @param bool $email
     * @param bool $includeComment
     * @return mixed
     */
    public function createInvoice($orderIncrementId, $itemsQty = array(), $comment = null, $email = false, $includeComment = false)
    {
        $template_invoice = Mage::helper('webpos/config')->getWebposEmailTemplate('invoice');
        if (isset($template_invoice['guest']) && $template_invoice['guest'] != '') {
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_GUEST_TEMPLATE, $template_invoice['guest']);
        }
        if (isset($template_invoice['customer']) && $template_invoice['customer'] != '') {
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_TEMPLATE, $template_invoice['customer']);
        }
        $api = Mage::getModel('sales/order_invoice_api');
        try{
            $invoiceId = $api->create($orderIncrementId, $itemsQty, $comment, $email, $includeComment);
        }catch(\Exception $e){
            $invoiceId = false;
        }
        return $invoiceId;
    }

    /**
     * Create shipment
     * @param $orderIncrementId
     * @param $itemsQty
     * @param null $comment
     * @param bool $email
     * @param bool $includeComment
     * @return mixed
     */
    public function createShipment($orderIncrementId, $itemsQty = array(), $comment = null, $email = false, $includeComment = false)
    {
        $template_shipment = Mage::helper('webpos/config')->getWebposEmailTemplate('shipment');
        if (isset($template_shipment['guest']) && $template_shipment['guest'] != '') {
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_GUEST_TEMPLATE, $template_shipment['guest']);
        }
        if (isset($template_shipment['customer']) && $template_shipment['customer'] != '') {
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_TEMPLATE, $template_shipment['customer']);
        }
        $api = Mage::getModel('sales/order_shipment_api');
        try{
            $shipmentId = $api->create($orderIncrementId, $itemsQty, $comment, $email, $includeComment);
        }catch(\Exception $e){
            $shipmentId = false;
        }
        return $shipmentId;
    }

    /**
     *
     * @param string $customerId
     * @param \Magestore\Webpos\Api\Data\CartItemInterface[] $items
     * @param \Magestore\Webpos\Api\Data\Checkout\PaymentInterface $payment
     * @param \Magestore\Webpos\Api\Data\Checkout\ShippingInterface $shippingMethod
     * @param \Magestore\Webpos\Api\Data\Checkout\ConfigInterface $config
     * @param string $couponCode
     * @return string
     */
    public function checkGiftcard($customerId, $items, $payment, $shipping, $config, $couponCode = "")
    {
        $response = array();
        $session = $this->getSession();
        $session->clear();
        $session->setCurrencyId($config->getCurrencyCode());
        $session->setData('checking_promotion', true);

        $storeAddress = $this->getStoreAddressData();
        if ($customerId) {
            $customer = Mage::getModel('customer/customer')->load($customerId);
            if ($customer->getId()) {
                $session->setCustomerId($customerId);
                $this->getQuote()->setCustomer($customer);
            }
        } else {
            $this->getQuote()->setCustomerIsGuest(true);
            $this->getQuote()->setCustomerEmail($storeAddress['email']);
        }
        $this->setWebPosBillingAddress($payment, $storeAddress);

        $this->initRuleData();
        $this->_processCart($items);
        $this->setWebPosBillingAddress($payment, $storeAddress);
        if (!$this->getQuote()->isVirtual()) {
            $this->setWebPosShippingAddress($shipping, $storeAddress);
            $this->saveShippingMethod($shipping->getMethod());
        }
        $this->_savePaymentData($payment);
        $this->_setCouponCode($couponCode);
        $quote = $this->getQuote();
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        $giftVoucher = Mage::getModel('giftvoucher/giftvoucher')->loadByCode($couponCode);
        if ($giftVoucher->getId() && $giftVoucher->getBaseBalance() > 0
            && $giftVoucher->getStatus() == Magestore_Giftvoucher_Model_Status::STATUS_ACTIVE
        ) {
            $giftVoucher->addToSession($session);
            $quote = $this->getQuote();
            if ($giftVoucher->validate($quote->setQuote($quote))) {
                $response['data']['base_balance'] = $giftVoucher->getBaseBalance();
                $response['data']['balance'] = $giftVoucher->getBalance();
                $response['data']['code'] = $giftVoucher->getGiftCode();
                $response['success'] = true;
            } else {
                $response['error'] = true;
                $response['message'] = Mage::helper('webpos')->__('Can’t use this gift code since its conditions haven’t been met.');
            }
        } else {
            $response['error'] = true;
            $response['message'] = Mage::helper('webpos')->__('Gift code is invalid');
        }
        $this->_removeCurrentQuote();
        return $response;
    }

    /**
     * Remove current quote
     */
    protected function _removeCurrentQuote()
    {
        try {
            $quote = $this->getQuote();
            if ($quote && $quote->getId()) {
                $quote->delete();
                $this->_quote = false;
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'system.log', true);
        }
    }

    /**
     * Update quantity of order quote items
     *
     * @param   array $data
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    public function updateQuoteItems($data)
    {
        if (is_array($data)) {
            try {
                foreach ($data as $itemId => $info) {
                    if (!empty($info['configured'])) {
                        $item = $this->getQuote()->updateItem($itemId, new Varien_Object($info));
                        $itemQty = (float)$item->getQty();
                    } else {
                        $item = $this->getQuote()->getItemById($itemId);
                        $itemQty = (float)$info['qty'];
                    }

                    if ($item) {
                        if ($item->getProduct()->getStockItem()) {
                            if (!$item->getProduct()->getStockItem()->getIsQtyDecimal()) {
                                $itemQty = (int)$itemQty;
                            } else {
                                $item->setIsQtyDecimal(1);
                            }
                        }
                        $itemQty = $itemQty > 0 ? $itemQty : 1;
                        if (isset($info['custom_price'])) {
                            $itemPrice = $this->_parseCustomPrice($info['custom_price']);
                        } else {
                            $itemPrice = null;
                        }
                        $noDiscount = !isset($info['use_discount']);

                        if (empty($info['action']) || !empty($info['configured'])) {
                            $item->setQty($itemQty);
                            $item->setCustomPrice($itemPrice);
                            $item->setOriginalCustomPrice($itemPrice);
                            $item->setNoDiscount($noDiscount);
                            $item->getProduct()->setIsSuperMode(true);
                            $item->getProduct()->unsSkipCheckRequiredOption();
                            $item->checkData();
                        }
                        if (!empty($info['action'])) {
                            $this->moveQuoteItem($item, $info['action'], $itemQty);
                        }
                    }

                    if ($item->getHasError() && $item->getMessage()) {
                        throw new Exception($item->getMessage());
                    }
                    if ($item->getMessage()) {
                        $this->getQuote()->addMessage($item->getMessage(), 'notice');
                    }
                }
            } catch (Mage_Core_Exception $e) {
                $this->recollectCart();
                throw $e;
            } catch (Exception $e) {
                Mage::logException($e);
                throw $e;
            }
            $this->recollectCart();
        }
        return $this;
    }
}
