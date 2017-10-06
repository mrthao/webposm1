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
class Magestore_Webpos_Block_Adminhtml_Till_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        $form = new Varien_Data_Form ();
        $this->setForm($form);
        $data = array();
        if(Mage::registry('till_data')) {
            $data = Mage::registry('till_data')->getData();
        }
        $fieldset = $form->addFieldset('Till_form', array(
            'legend' => Mage::helper('webpos')->__('Cash Drawer Information')
                ));

        $fieldset->addField('till_name', 'text', array(
            'label' => Mage::helper('webpos')->__('Cash Drawer Name'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'till_name'
        ));
        $fieldset->addField('location_id', 'select', array(
            'label' => Mage::helper('webpos')->__('Location'),
            'required' => true,
            'name' => 'location_id',
            'values' => Mage::getSingleton('webpos/userlocation')->toOptionArray(),
        ));
        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('webpos')->__('Status'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'status',
            'values' => Mage::getSingleton('webpos/status')->getOptionArray()
        ));
        $form->setValues($data);
        return parent::_prepareForm();
    }

}
