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

class Magestore_Webpos_Block_Adminhtml_Till_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    public function __construct() {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'webpos';
        $this->_controller = 'adminhtml_till';

        $this->_updateButton('save', 'label', Mage::helper('webpos')->__('Save Cash Drawer'));
        $this->_updateButton('delete', 'label', Mage::helper('webpos')->__('Delete Cash Drawer'));

        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
                ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('marketingautomation_content') == null)
                    tinyMCE.execCommand('mceAddControl', false, 'till_content');
                else
                    tinyMCE.execCommand('mceRemoveControl', false, 'till_content');
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText() {
        if (Mage::registry('till_data') && Mage::registry('till_data')->getId()
        ) {
            return Mage::helper('webpos')->__("Edit Cash Drawer '%s'", $this->htmlEscape(Mage::registry('till_data')->getTillName())
            );
        }
        return Mage::helper('webpos')->__('Add Cash Drawer');
    }

}
