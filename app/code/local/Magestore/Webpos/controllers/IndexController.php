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

class Magestore_Webpos_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('webpos_head')->setTitle('Web POS');
        $this->renderLayout();
    }

    public function reinstallDbAction() {
        $installer = new Mage_Core_Model_Resource_Setup();
        $installer->startSetup();
        $webposHelper = Mage::helper("webpos");
        $installer->run("
		  DROP TABLE IF EXISTS {$installer->getTable('webpos_admin')};
		  DROP TABLE IF EXISTS {$installer->getTable('webpos_survey')};
		  DROP TABLE IF EXISTS {$installer->getTable('webpos_order')};
		  DROP TABLE IF EXISTS {$installer->getTable('webpos_products')};
		  DROP TABLE IF EXISTS {$installer->getTable('webpos_xreport')};
		");
        if (!$webposHelper->columnExist($installer->getTable('webpos_user'), 'till_ids')) {
            $installer->run(" ALTER TABLE {$installer->getTable('webpos_user')} ADD `till_ids` VARCHAR( 255 ) default 'all'; ");
        }
        $webposHelper->addNewTables();
        $webposHelper->addAdditionalFields();
        $webposHelper->addWebposVisibilityAttribute();

        $installer->endSetup();
        $this->getResponse()->setBody('ok');
    }
}