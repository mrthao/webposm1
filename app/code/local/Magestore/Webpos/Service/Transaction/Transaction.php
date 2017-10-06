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

class Magestore_Webpos_Service_Transaction_Transaction extends Magestore_Webpos_Service_Abstract
{
    /**
     * @param $transactionData
     * @return mixed
     */
    public function saveTransaction($transactionData){
        $data = array();
        $message = array();
        $status = Magestore_Webpos_Api_ResponseInterface::STATUS_SUCCESS;
        if(!empty($transactionData) && is_array($transactionData)){
            $model = $this->_getModel('webpos/transaction');
            $model->setData($transactionData);
            try{
                $model->save();
            }catch (Exception $e){
                $status = Magestore_Webpos_Api_ResponseInterface::STATUS_ERROR;
                $message[] = $e->getMessage();
            }
        }
        return $this->getResponseData($data, $message, $status, false);
    }

    /**
     * @param $cashDrawerId
     * @return mixed
     */
    public function getList($cashDrawerId){
        $data = array();
        $message = array();
        $status = Magestore_Webpos_Api_ResponseInterface::STATUS_SUCCESS;
        try{
            $resource = $this->_getResource('webpos/transaction_collection');
            $transactions = $resource->getActiveTransactions($cashDrawerId);
            $transactions->setOrder('created_at', 'DESC');
            $transactionsData = array();
            foreach ($transactions as $transaction) {
                $transactionsData[] = $transaction->load($transaction->getId())->getData();
            }
            $data['items'] = $transactionsData;
            $data['total_count'] = $transactions->getSize();
        }catch (Exception $e){
            $status = Magestore_Webpos_Api_ResponseInterface::STATUS_ERROR;
            $message[] = $e->getMessage();
        }
        return $this->getResponseData($data, $message, $status, false);
    }

    /**
     * @param $cashDrawerId
     */
    public function deactiveByCashDrawerId($cashDrawerId){
        $transactions = $this->_getResource('webpos/transaction_collection');
        $transactions->deactiveByCashDrawerId($cashDrawerId);
    }

    /**
     * @param Magestore_Webpos_Model_Zreport $zReportModel
     */
    public function openStoreAfterClose(Magestore_Webpos_Model_Zreport $zReportModel){
        if($zReportModel->getCashLeft() > 0){
            $openingData = array(
                'till_id' => $zReportModel->getTillId(),
                'staff_id' => $zReportModel->getStaffId(),
                'order_increment_id' => 0,
                'amount' => $zReportModel->getCashLeft(),
                'base_amount' => $zReportModel->getBaseCashLeft(),
                'transaction_currency_code' => $zReportModel->getReportCurrencyCode(),
                'base_currency_code' => $zReportModel->getBaseCurrencyCode(),
                'note' => $this->__('Opening'),
                'is_opening' => Magestore_Webpos_Model_Transaction::TRUE
            );
            $this->saveTransaction($openingData);
        }
    }
}
