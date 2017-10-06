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

class Magestore_Webpos_Block_Adminhtml_Zreport_Print extends Mage_Adminhtml_Block_Template
{

    public function getZreportData(){
        $model = Mage::getModel('webpos/zreport');
        $id = Mage::app()->getRequest()->getParam('id');
        if($id){
            $model->load($id);
        }
        $data = $model->getData();
        if(!empty($data['sale_by_payments'])){
            $payments = array();
            $sale_by_payments = str_replace('[', '', $data['sale_by_payments']);
            $sale_by_payments = str_replace(']', '', $sale_by_payments);
            $sale_by_payments = explode('},', $sale_by_payments);
            if(!empty($sale_by_payments)){
                foreach ($sale_by_payments as $payment){
                    $payment = (strpos($payment, '}') === false)?$payment.'}':$payment;
                    $paymentData = Zend_Json::decode($payment);
                    if (isset($paymentData['base_currency_code'])) {
                        if($paymentData['base_currency_code'] != $paymentData['report_currency_code']){
                            $paymentData['base_payment_amount'] = Mage::helper('webpos')->convertPrice($paymentData['base_payment_amount'], $data['base_currency_code'], $data['report_currency_code']);
                        }
                    }
                    $payments[] = $paymentData;
                }
            }
            $data['sale_by_payments'] = $payments;
        }
        if(!empty($data['sales_summary'])){
            $data['sales_summary'] = Zend_Json::decode($data['sales_summary']);
        }
        if(!empty($data['staff_id'])){
            $staff = Mage::getModel('webpos/user')->load($data['staff_id']);
            $data['staff_name'] = ($staff->getDisplayName())?$staff->getDisplayName():$staff->getUsername();
        }else{
            $data['staff_name'] = '';
        }
        return $data;
    }

    public function formatReportPrice($price){
        return Mage::helper('core')->currency($price, true, false);
    }

    public function formatReportDate($date){
        return Mage::helper('webpos')->formatDate($date);
    }
}