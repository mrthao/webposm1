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

class Magestore_Webpos_Helper_Shift extends Mage_Core_Helper_Abstract
{
    /**
     * @return int
     */
    public function getCurrentShiftId()
    {
        $staffId = Mage::helper('webpos/permission')->getCurrentUser();
        $staffModel = Mage::getModel('webpos/user')->load($staffId);
        $locationId = $staffModel->getLocationId();
        //@@TODO model shift
//        $shiftModel = Mage::getModel('webpos/shift_shift');
//        $shiftId = $shiftModel->getCurrentShiftId($staffId);
        $shiftId = 9999;
        return $shiftId;
    }


}
