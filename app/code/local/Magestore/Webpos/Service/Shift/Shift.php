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

class Magestore_Webpos_Service_Shift_Shift extends Magestore_Webpos_Service_Abstract
{

    /**
     * @param $zReportData
     * @return mixed
     */
    public function closeShift($zReportData){
        $data = array();
        $message = array();
        $status = Magestore_Webpos_Api_ResponseInterface::STATUS_SUCCESS;
        try{
            $model = $this->_getModel('webpos/zreport');
            $model->setData($zReportData);
            $model->save();
            $transactionService = $this->_createService('transaction_transaction');
            $transactionService->deactiveByCashDrawerId($model->getTillId());
            $transactionService->openStoreAfterClose($model);
        }catch (Exception $e){
            $status = Magestore_Webpos_Api_ResponseInterface::STATUS_ERROR;
            $message[] = $e->getMessage();
        }
        return $this->getResponseData($data, $message, $status, false);
    }

    /**
     * @param $cashDrawerId
     * @return mixed
     */
    public function getShiftData($cashDrawerId){
        $data = array();
        $message = array();
        $status = Magestore_Webpos_Api_ResponseInterface::STATUS_SUCCESS;
        try{
            $data['sales_summary'] = $this->_getSalesSummary($cashDrawerId);
            $data['sales_by_payments'] = $this->_getSalesByPayments($cashDrawerId);
            $data['base_balance'] = $this->_getBaseBalance($cashDrawerId);
            $data['base_opening_amount'] = $this->_getBaseOpeningAmount($cashDrawerId);
            $data['open_at'] = $this->_getOpeningAt($cashDrawerId);
        }catch (Exception $e){
            $status = Magestore_Webpos_Api_ResponseInterface::STATUS_ERROR;
            $message[] = $e->getMessage();
        }
        return $this->getResponseData($data, $message, $status, false);
    }

    /**
     * @param $cashDrawerId
     * @return float
     */
    protected function _getBaseOpeningAmount($cashDrawerId){
        $resource = $this->_getResource('webpos/transaction_collection');
        $transactions = $resource->getActiveTransactions($cashDrawerId);
        $transactions->addFieldToFilter('is_opening', Magestore_Webpos_Model_Transaction::TRUE);
        return ($transactions->getSize() > 0)?$transactions->getFirstItem()->getBaseAmount():0;
    }

    /**
     * @param $cashDrawerId
     * @return float
     */
    protected function _getBaseBalance($cashDrawerId){
        $baseBalance = 0;
        $resource = $this->_getResource('webpos/transaction_collection');
        $transactions = $resource->getActiveTransactions($cashDrawerId);
        if($transactions->getSize() > 0){
            foreach ($transactions as $transaction){
                $baseBalance += $transaction->getBaseAmount();
            }
        }
        return $baseBalance;
    }

    /**
     * @param $cashDrawerId
     * @return string
     */
    protected function _getOpeningAt($cashDrawerId){
        $collection = $this->_getResource('webpos/zreport_collection')->addFieldToFilter('till_id', $cashDrawerId);
        if($collection->getSize() > 0){
            $collection->setOrder('id', 'DESC');
            $lastTimeUsed = $collection->getFirstItem()->getClosedAt();
        }else{
            $lastTimeUsed = '2016-12-19 00:00:00';
        }
        return $lastTimeUsed;
    }

    /**
     * @param $cashDrawerId
     * @return array
     */
    protected function _getSalesByPayments($cashDrawerId){
        $sales = array();
        $sales[Magestore_Webpos_Helper_Payment::CASH_PAYMENT_CODE] = array(
            'payment_method' => Magestore_Webpos_Helper_Payment::CASH_PAYMENT_CODE,
            'method_title' => $this->_getHelper('webpos/payment')->getCashMethodTitle(),
            'base_payment_amount' => $this->_getBaseBalance($cashDrawerId),
        );
        $openingAmount = $this->_getOpeningAt($cashDrawerId);
        $orderCollection = $this->_getResource('sales/order_collection');
        $orderCollection->addFieldToFilter('webpos_till_id', $cashDrawerId);
        $orderCollection->addFieldToFilter('created_at', array('from' => $openingAmount));
        $orderIds = $orderCollection->getAllIds();
        if(!empty($orderIds)){
            $collection = $this->_getModel('webpos/payment_orderPayment')->getCollection();
            $collection->addFieldToFilter('till_id', $cashDrawerId);
            $collection->addFieldToFilter('order_id', array('in' => $orderIds));
            $collection->addFieldToFilter('method', array('neq' => Magestore_Webpos_Helper_Payment::CASH_PAYMENT_CODE));
            if($collection->getSize() > 0){
                foreach ($collection as $orderPayment){
                    if(isset($sales[$orderPayment->getMethod()])){
                        $sales[$orderPayment->getMethod()]['base_payment_amount'] += $orderPayment->getBasePaymentAmount();
                    }else{
                        $sales[$orderPayment->getMethod()] = array(
                            'payment_method' => $orderPayment->getMethod(),
                            'method_title' => $orderPayment->getMethodTitle(),
                            'base_payment_amount' => $orderPayment->getBaseRealAmount()
                        );
                    }
                }
            }
        }
        return array_values($sales);
    }

    /**
     * @param $cashDrawerId
     * @return array
     */
    protected function _getSalesSummary($cashDrawerId){
        $salesSummary = array(
            'base_grand_total' => 0,
            'base_total_refunded' => 0,
            'base_discount_amount' => 0
        );
        $openingAmount = $this->_getOpeningAt($cashDrawerId);
        $orderCollection = $this->_getResource('sales/order_collection');
        $orderCollection->addFieldToFilter('webpos_till_id', $cashDrawerId);
        $orderCollection->addFieldToFilter('created_at', array('from' => $openingAmount));
        if($orderCollection->getSize() > 0){
            foreach ($orderCollection as $order){
                $salesSummary['base_grand_total'] += ($order->getBaseGrandTotal())?$order->getBaseGrandTotal():0;
                $salesSummary['base_total_refunded'] += ($order->getBaseTotalRefunded())?$order->getBaseTotalRefunded():0;
                $salesSummary['base_discount_amount'] += ($order->getBaseDiscountAmount())?$order->getBaseDiscountAmount():0;
            }
        }
        return $salesSummary;
    }
}
