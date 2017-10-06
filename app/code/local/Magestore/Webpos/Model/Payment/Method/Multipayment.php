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

/*
 * Web POS by Magestore.com
 * Version 2.3
 * Updated by Daniel - 12/2015
 */

class Magestore_Webpos_Model_Payment_Method_Multipayment extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'multipaymentforpos';
    protected $_infoBlockType = 'webpos/payment_method_multipayment_info_multipayment';
    protected $_formBlockType = 'webpos/payment_method_multipayment_multipaymentforpos';

    public function isAvailable($quote = null) {
        $isWebposApi = Mage::helper('webpos/permission')->validateRequestSession();
        $routeName = Mage::app()->getRequest()->getRouteName();
        $multipaymentEnabled = Mage::helper('webpos/payment')->isMultiPaymentEnabled();
        if (($routeName == "webpos" || $isWebposApi) && $multipaymentEnabled == true)
            return true;
        else
            return false;
    }

    public function assignData($data) {
        $info = $this->getInfoInstance();
        if ($data->getData('cashforpos_ref_no')) {
            $info->setData('cashforpos_ref_no', $data->getData('cashforpos_ref_no'));
        }
        if ($data->getData('ccforpos_ref_no')) {
            $info->setData('ccforpos_ref_no', $data->getData('ccforpos_ref_no'));
        }
        if ($data->getData('cp1forpos_ref_no')) {
            $info->setData('cp1forpos_ref_no', $data->getData('cp1forpos_ref_no'));
        }
        if ($data->getData('cp2forpos_ref_no')) {
            $info->setData('cp2forpos_ref_no', $data->getData('cp2forpos_ref_no'));
        }

        if ($data->getData('codforpos_ref_no')) {
            $info->setData('codforpos_ref_no', $data->getData('codforpos_ref_no'));
        }

        return $this;
    }
}
