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
// Abel edit: new - add new column
$installer = $this;

$installer->startSetup();

$webposHelper = Mage::helper("webpos");

if (!$webposHelper->columnExist($this->getTable('sales_flat_order_item'), 'comment')) {
    $installer->run(" ALTER TABLE {$this->getTable('sales_flat_order_item')} ADD `comment` VARCHAR( 255 ) default ''; ");
}
if (!$webposHelper->columnExist($this->getTable('sales_flat_quote_item'), 'comment')) {
    $installer->run(" ALTER TABLE {$this->getTable('sales_flat_quote_item')} ADD `comment` VARCHAR( 255 ) default ''; ");
}

$installer->endSetup();