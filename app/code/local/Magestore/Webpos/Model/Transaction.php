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

/**
 * Class Magestore_Webpos_Model_Transaction
 */
class Magestore_Webpos_Model_Transaction extends Mage_Core_Model_Abstract
{

    const STATUS_ACTIVE = '1';
    const STATUS_INACTIVE = '2';

    const TRUE = '1';
    const FALSE = '0';

    /**
     * Contructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('webpos/transaction');
    }

    /**
     * @return mixed
     */
    protected function _beforeSave()
    {
        if(!$this->getCreatedAt()){
            $currentTime = strftime('%Y-%m-%d %H:%M:%S', Mage::getModel('core/date')->gmtTimestamp());
            $this->setCreatedAt($currentTime);
        }
        if(!$this->getStatus()){
            $this->setStatus(self::STATUS_ACTIVE);
        }
        if($this->getIsOpening() !== self::TRUE){
            $this->setIsOpening(self::FALSE);
        }
        $this->setIsManual(($this->getOrderIncrementId())?self::FALSE:self::TRUE);
        return parent::_beforeSave();
    }

    /**
     * @return mixed
     */
    protected function _afterLoad()
    {
        $status = $this->getStatus();
        $isManual = $this->getIsManual();
        $isOpening = $this->getIsOpening();
        $orderIncrementId = $this->getOrderIncrementId();
        $createdAt = $this->getCreatedAt();
        $createdAt = Mage::app()->getLocale()->storeDate(
            Mage::app()->getStore(),
            Varien_Date::toTimestamp($createdAt),
            true
        )->toString("MM/dd/YYYY HH:mm:ss");
        $this->setCreatedAt($createdAt);
        $this->setIsActive(($status == self::STATUS_ACTIVE)?true:false);
        $this->setIsManual(($isManual == self::TRUE)?true:false);
        $this->setIsOpening(($isOpening == self::TRUE)?true:false);
        if($orderIncrementId == 0){
            $this->setOrderIncrementId(Mage::helper('webpos')->__('Manual'));
        }
        return parent::_afterLoad();
    }
}