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

class Magestore_Webpos_Service_Checkout_Response extends Magestore_Webpos_Service_Abstract
{
    /**
     * @param Mage_Sales_Model_Quote $quote
     */
    public function initQuote($data){
        if($data instanceof Mage_Sales_Model_Quote){
            $this->getCheckoutModel()->setQuote($data);
        }elseif(is_array($data) && isset($data[Magestore_Webpos_Api_Cart_QuoteDataInitInterface::QUOTE_ID])){
            $this->getCheckoutModel()->initData($data);
        }
    }

    /**
     * @return mixed
     */
    public function getQuoteItemsSummaryQty(){
        return $this->getQuote()->getItemsSummaryQty();
    }

    /**
     * @return array
     */
    public function getQuoteItems(){
        $result = array();
        $items = $this->getQuote()->getAllVisibleItems();
        if(count($items)){
            foreach ($items as $item){
                $result[$item->getId()] = $item->getData();
                $result[$item->getId()]['offline_item_id'] =  $item->getBuyRequest()->getData('item_id');
                $result[$item->getId()]['image_url'] =  $this->_getHelper('catalog/image')->init($item->getProduct(), 'thumbnail')->resize('500')->__toString();
                $result[$item->getId()]['minimum_qty'] =  $item->getProduct()->getStockItem()->getMinSaleQty();
                $result[$item->getId()]['maximum_qty'] =  $item->getProduct()->getStockItem()->getMaxSaleQty();
                $result[$item->getId()]['qty_increment'] =  $item->getProduct()->getStockItem()->getQtyIncrements();
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getTotals(){
        $totals = $this->getQuote()->getTotals();
        $totalsResult = array();
        foreach ($totals as $total) {
            $data = $total->getData();
            if($this->_helper->isRewardPointsEnable()){
                if($data['code'] == 'rewardpoints_label') {
                    $data['title'] = $this->_helper->__('Customer will earn');
                    $data['value'] = $this->_getHelper('rewardpoints/calculation_earning')->getTotalPointsEarning($this->getQuote());
                }
            }
            $totalsResult[] = $data;
        }
        return $totalsResult;
    }

    /**
     * @return array
     */
    public function getShipping(){
        $shippingList = array();
        $api = $this->_getCheckoutApi('shipping');
        $list = $api->getShippingMethodsList($this->getQuote()->getId());
        if(count($list) > 0){
            $shippingHelper = $this->_getHelper('webpos/shipping');
            foreach ($list as $data) {
                $methodCode = $data['code'];
                $isDefault = '0';
                if($methodCode == $shippingHelper->getDefaultShippingMethod()) {
                    $isDefault = '1';
                }
                $methodTitle = $data['carrier_title'].' - '.$data['method_title'];
                $methodPrice = ($data['price'] != null) ? $data['price'] : '0';
                $methodPriceType = '';
                $methodDescription = ($data['method_description'] != null) ?$data['method_description'] : '0';
                $methodSpecificerrmsg = (isset($data['error_message']) && $data['error_message'] != null) ?$data['error_message'] : '';

                $shippingModel =  $this->_getModel('webpos/shipping_shipping');
                $shippingModel->setCode($methodCode);
                $shippingModel->setTitle($methodTitle);
                $shippingModel->setPrice($methodPrice);
                $shippingModel->setDescription($methodDescription);
                $shippingModel->setIsDefault($isDefault);
                $shippingModel->setErrorMessage($methodSpecificerrmsg);
                $shippingModel->setPriceType($methodPriceType);
                $shippingList[] = $shippingModel->getData();
            }
        }
        return $shippingList;
    }

    /**
     * @return mixed
     */
    public function getPayment(){
        $api = $this->_getCheckoutApi('payment');
        $list = $api->getPaymentMethodsList($this->getQuote()->getId());
        $paymentList = array();
        if(count($list) > 0) {
            $paymentHelper = $this->_getHelper('webpos/payment');
            foreach ($list as $data) {
                $code = $data['code'];
                $title = $data['title'];
                $ccTypes = $data['cc_types'];
//                if (!$paymentHelper->isAllowOnWebPOS($code) || $paymentHelper->isWebposPayment($code))
                if ($paymentHelper->isWebposPayment($code))
                    continue;
                $iconClass = 'icon-iconPOS-payment-cp1forpos';
                $isDefault = ($code == $paymentHelper->getDefaultPaymentMethod())?Magestore_Webpos_Api_PaymentInterface::YES:Magestore_Webpos_Api_PaymentInterface::NO;
                $isReferenceNumber = (!$ccTypes)?Magestore_Webpos_Api_PaymentInterface::YES:Magestore_Webpos_Api_PaymentInterface::NO;
                $isPayLater = Magestore_Webpos_Api_PaymentInterface::NO;

                $paymentModel =  $this->_getModel('webpos/payment_payment');
                $paymentModel->setCode($code);
                $paymentModel->setIconClass($iconClass);
                $paymentModel->setTitle($title);
                $paymentModel->setInformation('');
                $paymentModel->setType(($ccTypes)?$ccTypes:Magestore_Webpos_Api_PaymentInterface::NO);
                $paymentModel->setIsDefault($isDefault);
                $paymentModel->setIsReferenceNumber($isReferenceNumber);
                $paymentModel->setIsPayLater($isPayLater);
                $paymentModel->setMultiable(false);
                $paymentSource = $this->_getModel('webpos/source_adminhtml_payment');
                $formData = $paymentSource->getPaymentFormInfo($code);
                $paymentModel->setTemplate($formData['template']);
                $paymentModel->setFormData($formData['data']);
                $paymentList[] = $paymentModel->getData();
            }
        }
        return $paymentList;
    }

    /**
     * @param $order
     * @return array
     */
    public function getOrderSuccessData($order){
        $data = array();
        if($order){
            if($order instanceof Mage_Sales_Model_Order){
                //reload order to get the real data changed after create invoice / shipment
                $order = $this->_getModel('sales/order')->load($order->getId());
            }else{
                $order = $this->_getModel('sales/order')->load($order);
            }
            if($order->getId()){
                $data = $this->_getHelper('webpos/order')->getAllOrderInfo($order)->getData();
                $data['webpos_order_payments'] = $this->_getWebposPaidPayment($order);
            }
        }
        return $data;
    }

    /**
     * @param $order
     * @return array
     */
    private function _getWebposPaidPayment($order){
        $payments = array();
        if($order){
            $order = ($order instanceof Mage_Sales_Model_Order)?$order:$this->_getModel('sales/order')->load($order);
            if($order->getId()){
                $collection = $this->_getModel('webpos/payment_orderPayment')->getCollection()->addFieldToFilter('order_id', $order->getId());
                if($collection->getSize() > 0){
                    foreach ($collection as $payment) {
                        $payments[] = $payment->getData();
                    }
                }
            }
        }
        return $payments;
    }
}
