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
 * Marketingautomation Edit Form Content Tab Block
 *
 * @category Magestore
 * @package Magestore_Webpos
 * @author Magestore Developer
 */
class Magestore_Webpos_Block_Adminhtml_User_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {

    /**
     * prepare tab form's information
     *
     * @return Magestore_Marketingautomation_Block_Adminhtml_Contact_Edit_Tab_Form
     */
    protected function _prepareForm() {
        $form = new Varien_Data_Form ();
        $this->setForm($form);
        $data = array();
        if (Mage::registry('user_data')) {
            $data = Mage::registry('user_data')->getData();
        }
        $fieldset = $form->addFieldset('User_form', array(
            'legend' => Mage::helper('webpos')->__('User Information')
        ));

        $fieldset->addField('username', 'text', array(
            'label' => Mage::helper('webpos')->__('User Name'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'username'
        ));
        if ((isset($data['user_id']) && $data['user_id']) || $this->getRequest()->getParam('id')) {
            $fieldset->addField('password', 'password', array(
                'label' => Mage::helper('webpos')->__('New Password'),
                'name' => 'new_password',
                'class' => 'input-text validate-admin-password',
            ));
            $fieldset->addField('password_confirmation', 'password', array(
                'label' => Mage::helper('webpos')->__('Password Confirmation'),
                'name' => 'password_confirmation',
                'class' => 'input-text validate-cpassword',
            ));
        } else {
            $fieldset->addField('password', 'password', array(
                'label' => Mage::helper('webpos')->__('Password'),
                'name' => 'password',
                'required' => true,
                'class' => 'input-text required-entry validate-admin-password',
            ));
            $fieldset->addField('password_confirmation', 'password', array(
                'label' => Mage::helper('webpos')->__('Password Confirmation'),
                'name' => 'password_confirmation',
                'required' => true,
                'class' => 'input-text required-entry validate-cpassword',
            ));
        }
        $fieldset->addField('display_name', 'text', array(
            'label' => Mage::helper('webpos')->__('Display Name'),
            'required' => true,
            'name' => 'display_name'
        ));
        $fieldset->addField('email', 'text', array(
            'label' => Mage::helper('webpos')->__('Email Address'),
            'class' => 'required-entry validate-email',
            'required' => true,
            'name' => 'email'
        ));
        $fieldset = $form->addFieldset('User_setting_form', array(
            'legend' => Mage::helper('webpos')->__('User Settings')
        ));

        $fieldset->addField('monthly_target', 'text', array(
            'label' => Mage::helper('webpos')->__('Monthly Target Budget'),
            'class' => 'validate-bumber',
            'name' => 'monthly_target'
        ));

        $fieldset->addField('customer_group', 'multiselect', array(
            'label' => Mage::helper('webpos')->__('Customer Group'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'customer_group',
            'values' => Mage::getSingleton('webpos/customergroup')->getOptionArray()
        ));
        $fieldset->addField('location_id', 'select', array(
            'label' => Mage::helper('webpos')->__('Location'),
            'required' => true,
            'name' => 'location_id',
            'values' => Mage::getSingleton('webpos/userlocation')->toOptionArray(),
        ));
        if(!empty($data['location_id'])){
            $fieldset->addField('till_ids', 'multiselect', array(
                'label' => Mage::helper('webpos')->__('Cash Drawers'),
                'class' => 'required-entry',
                'required' => true,
                'name' => 'till_ids',
                'values' => Mage::getSingleton('webpos/till')->getOptionArray($data['location_id']),
            ));
        }

        $fieldset->addField('role_id', 'select', array(
            'label' => Mage::helper('webpos')->__('Role'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'role_id',
            'values' => Mage::getSingleton('webpos/role')->toOptionArray()
        ));
        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('webpos')->__('Status'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'status',
            'values' => Mage::getSingleton('webpos/status')->getOptionArray()
        ));
        unset($data['password']);
        unset($data['password_confirmation']);
        $form->setValues($data);
        return parent::_prepareForm();
    }

}
