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

class Magestore_Webpos_Model_Observer_General extends Magestore_Webpos_Model_Observer_Abstract
{
    /**
     * @param $observer
     * @return $this
     */
    public function webposEmptyCartAfter($observer)
    {
        try{
            $checkoutSession = $this->_getModel('checkout/session');
            if ($this->_helper->isRewardPointsEnable()) {
                $checkoutSession->setUsePoint(false);
                $checkoutSession->setRewardSalesRules('use_point', array());
            }
            if ($this->_helper->isGiftCardEnable()) {
                if ($checkoutSession->getUseGiftCard()) {
                    $checkoutSession->setUseGiftCard(null)
                        ->setGiftCodes(null)
                        ->setBaseAmountUsed(null)
                        ->setBaseGiftVoucherDiscount(null)
                        ->setGiftVoucherDiscount(null)
                        ->setCodesBaseDiscount(null)
                        ->setCodesDiscount(null)
                        ->setGiftMaxUseAmount(null);
                }
                if ($checkoutSession->getUseGiftCardCredit()) {
                    $checkoutSession->setUseGiftCardCredit(null)
                        ->setMaxCreditUsed(null)
                        ->setBaseUseGiftCreditAmount(null)
                        ->setUseGiftCreditAmount(null);
                }
            }
        }catch(Exception $e){
            Mage::log($e->getMessage(), null, 'system.log', true);
        }
    }

    /**
     * @param $observer
     * @return $this
     */
    public function webposSaveCartAfter($observer)
    {
        try{
            if ($this->_helper->isRewardPointsEnable()) {

            }
        }catch(Exception $e){
            Mage::log($e->getMessage(), null, 'system.log', true);
        }
    }

    /**
     * @param $observer
     * @return $this
     */
    public function webposSendResponseBefore($observer)
    {
        try{
            $quote = $observer->getData('quote');
            $response = $observer->getData('response');
            $checkoutSession = $this->_getModel('checkout/session');
            if ($this->_helper->isRewardPointsEnable()) {
                if($response){
                    $spendingHelper = $this->_getHelper('rewardpoints/calculation_spending');
                    $usedPoint = $spendingHelper->getTotalPointSpent();
                    $data = $response->getResponseData();
                    if(!is_array($data)){
                        $data = array();
                    }
                    $data['rewardpoints']['used_point'] = $usedPoint;

                    if($quote){
                        $rule = $spendingHelper->getQuoteRule();
                        if($rule){
                            $maxPoint = $spendingHelper->getRuleMaxPointsForQuote($rule, $quote);
                            $data['rewardpoints']['max_point'] = $maxPoint;
                        }
                    }
                    $response->setResponseData($data);
                }
            }
            if ($this->_helper->isGiftCardEnable()) {
                if($response){
                    $data = $response->getResponseData();
                    if(!is_array($data)){
                        $data = array();
                    }
                    $isUseGiftcard = $checkoutSession->getUseGiftCard();
                    if($isUseGiftcard){
                        $giftcardData = array();
                        $baseAmountUsed = explode(',', $checkoutSession->getBaseAmountUsed());
                        $codes = array_filter(explode(',', $checkoutSession->getGiftCodes()));
                        if(count($codes) > 0){
                            foreach ($codes as $key => $code){
                                $giftcardData[$code] = $baseAmountUsed[$key];
                            }
                            $data['giftcard']['used_codes'] = $giftcardData;
                        }
                    }
                    if($quote){
                        $giftcardService = $this->_getModel('magestore_webpos_service_integration_giftcard');
                        $existedCode = $giftcardService->getCustomerExistedGiftCard($quote);
                        $data['giftcard']['existed_codes'] = $existedCode;
                    }
                    $response->setResponseData($data);
                }
            }
        }catch(Exception $e){
            Mage::log($e->getMessage(), null, 'system.log', true);
        }
    }

    /**
     * @param $observer
     * @return $this
     */
    public function webposRefundByCashAfter($observer)
    {
        try{
            $creditmemo = $observer->getData('creditmemo');
            if($creditmemo && $creditmemo->getId()){
                $order = $this->_getModel('sales/order')->load($creditmemo->getOrderId());
                $permission = $this->_getHelper('webpos/permission');
                $session = $permission->getCurrentSessionModel();
                if($session){
                    $transaction = $this->_getModel('webpos/transaction');
                    $transaction->setData(array(
                        Magestore_Webpos_Api_TransactionInterface::STAFF_ID => $session->getData('staff_id'),
                        Magestore_Webpos_Api_TransactionInterface::TILL_ID => $session->getData('current_till_id'),
                        Magestore_Webpos_Api_TransactionInterface::ORDER_INCREMENT_ID => 0,
                        Magestore_Webpos_Api_TransactionInterface::TRANSACTION_CURRENCY_CODE => $order->getData('order_currency_code'),
                        Magestore_Webpos_Api_TransactionInterface::BASE_CURRENCY_CODE => $order->getData('base_currency_code'),
                        Magestore_Webpos_Api_TransactionInterface::AMOUNT => -$creditmemo->getGrandTotal(),
                        Magestore_Webpos_Api_TransactionInterface::BASE_AMOUNT => -$creditmemo->getBaseGrandTotal(),
                        Magestore_Webpos_Api_TransactionInterface::NOTE => '#'.$creditmemo->getIncrementId().' - '.$this->__('Refund order').' #'.$order->getIncrementId()
                    ));
                    $transaction->save();
                }
            }
        }catch(Exception $e){
            Mage::log($e->getMessage(), null, 'system.log', true);
        }
    }

    /**
     * @param $observer
     * @return $this
     */
    public function webposPlaceOrderAfter($observer)
    {
        try{
            $orderId = $observer->getData('order_id');
            if($orderId){
                $order = $this->_getModel('sales/order')->load($orderId);
                if ($order && $order->getId()) {
                    $payment = $order->getPayment();
                    if ($payment && ($payment->getMethod() == 'pay_payment_instore')) {
                        $transactionId = $payment->getAdditionalInformation('paynl_transaction_id');
                        $payments = $this->_getModel('webpos/payment_orderPayment')->getCollection();
                        $payments->addFieldToFilter('method', 'pay_payment_instore');
                        $payments->addFieldToFilter('order_id', $orderId);
                        if($payments->getSize() > 0){
                            $orderPayment = $payments->getFirstItem();
                            $orderPayment->setData('reference_number', $transactionId);
                            $orderPayment->save();
                        }
                    }
                }
            }
        }catch(Exception $e){
            Mage::log($e->getMessage(), null, 'system.log', true);
        }
    }
}