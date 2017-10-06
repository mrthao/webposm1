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

class Magestore_Webpos_Model_Till extends Mage_Core_Model_Abstract
{
    const VALUE_ALL_TILL = 'all';

    /**
     * Contructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('webpos/till');
    }

    /**
     * Get enable till
     * @param bool $locationId
     * @return mixed
     */
    public function getEnableTill($locationId = false)
    {
        $collection = $this->getCollection()->addFieldToFilter('status', Magestore_Webpos_Model_Status::STATUS_ENABLED);
        if ($locationId) {
            $collection->addFieldToFilter('location_id', $locationId);
        }
        return $collection;
    }

    /**
     * For select element
     * @return array
     */
    public function toOptionArray($locationId = false)
    {
        $options = array();
        $collection = $this->getEnableTill($locationId);
        if ($collection->getSize() > 0) {
            $options = array(self::VALUE_ALL_TILL => Mage::helper('webpos')->__('---All Cash Drawer---'));
            foreach ($collection as $till) {
                $key = $till->getTillId();
                $value = $till->getTillName();
                $options [$key] = $value;
            }
        }
        return $options;
    }

    /**
     * For multiple select element
     * @return array
     */
    public function getOptionArray($locationId = false)
    {
        $options = array();
        $collection = $this->getEnableTill($locationId);
        if ($collection->getSize() > 0) {
            $options[] = array(
                'value' => self::VALUE_ALL_TILL,
                'label' => Mage::helper('webpos')->__('--- All ---')
            );
            foreach ($collection as $till) {
                $key = $till->getTillId();
                $value = $till->getTillName();
                $options[] = array(
                    'value' => $key,
                    'label' => $value
                );
            }
        }
        return $options;
    }
}