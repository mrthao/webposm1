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
 * API2 for catalog_product (Admin)
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Magestore_Webpos_Model_Api2_Product_Rest_Admin_V1 extends Magestore_Webpos_Model_Api2_Abstract
{
    /**
     *
     */
    const OPERATION_GET_PRODUCT_LIST = 'get';

    /**
     *
     */
    const OPERATION_GET_PRODUCT_ALLLIST = 'list';

    const OPERATION_GET_OPTIONS = 'getoptions';

    /**
     *
     */

    public function dispatch()
    {
        $this->_initStore();
        switch ($this->getActionType()) {
            case self::OPERATION_GET_PRODUCT_LIST:
                $result = $this->getProductList();
                $this->_render($result);
                $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_OK);
                break;
            case self::OPERATION_GET_PRODUCT_ALLLIST:
                $params = $this->getRequest()->getBodyParams();
                $result = $this->getAllProductList($params);
                $this->_render($result);
                $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_OK);
                break;
            case self::OPERATION_GET_OPTIONS:
                $result = $this->getProductOptionsInformation();
                $this->_render($result);
                $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_OK);
                break;
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function getAllProductList($params)
    {
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $isShowProductOutStock = Mage::helper('webpos')->getStoreConfig('webpos/general/show_product_outofstock');
        $itemIds = $params['itemsId'];
        $productMedia = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('status', 1)
            ->addAttributeToFilter('entity_id', array('in' => $itemIds));
        $store = $this->_getStore();
        $collection->setStoreId($store->getId());
        $collection->addAttributeToSelect('*')->joinField('qty',
            'cataloginventory/stock_item',
            'qty',
            'product_id=entity_id',
            '{{table}}.stock_id=' . Mage::getModel('cataloginventory/stock')->getId(),
            'left')
            ->getSelect()
            ->columns('entity_id AS id');

        /* allow to apply custom filters */
        Mage::dispatchEvent('webpos_catalog_product_collection_filter', array('collection' => $collection));

        $orderField = $this->getRequest()->getOrderField();

        if (null !== $orderField) {
            $collection->setOrder($orderField, $this->getRequest()->getOrderDirection());
        }

        /* @var Varien_Data_Collection_Db $customerCollection */
        $this->_applyFilterTo($collection);
        $result['total_count'] = $collection->getSize();
        $collection->load();
        $collection->addCategoryIds();

        $products = array();
        foreach ($collection as $productModel) {
            $productModel = Mage::getModel('webpos/catalog_product')->load($productModel->getId());
            $item = $productModel->getData();
            $item['category_ids'] = $productModel->getCategoryIds();
            $item['available_qty'] = $productModel->getStockItem()->getQty();
            $item['final_price'] = $productModel->getFinalPrice();
            if ($productModel->getImage() && $productModel->getImage() != 'no_selection') {
//                $item['image'] = $productMedia.$item['image'];
                $item['image'] = $productModel->getImageUrl();
            } else {
                $item['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) .
                    'magestore/webpos/catalog/category/image.jpg';
            }

            if ($item['stock_item']['is_in_stock']) {
                $item['isShowOutStock'] = 0;
            } else {
                $item['isShowOutStock'] = 1;
            }

            $products[] = $item;

        }
        $result['items'] = $products;


        return $result;

    }

    /**
     * @return mixed
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    public function getProductList()
    {
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $productMedia = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';
        $collection = Mage::getModel('catalog/product')->getCollection();
        $searchAttribute = Mage::helper('webpos')->getStoreConfig('webpos/product_search/product_attribute');
        $searchAttributeArray = explode(',', $searchAttribute);
        $collection->addAttributeToSelect($searchAttributeArray);
        $collection->addAttributeToFilter('status', 1)
//            ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
            ->addAttributeToFilter('type_id', array('in' => $this->getProductTypeIds()))
            ->addAttributeToFilter(array(
                array('attribute' => 'webpos_visible', 'is' => new \Zend_Db_Expr('NULL'), 'left'),
                array('attribute' => 'webpos_visible', 'eq' => Magestore_Webpos_Model_Source_Entity_Attribute_Source_Boolean::VALUE_YES, 'left'),
            ), '', 'left');
        $orderField = $this->getRequest()->getOrderField();

        if (null !== $orderField) {
            $collection->addAttributeToSort($orderField, $this->getRequest()->getOrderDirection());
        }
        $session = $this->getRequest()->getParam('session');
        $storeId = Mage::getModel('webpos/user_webpossession')->getStoreIdBySession($session);
        Mage::app()->setCurrentStore($storeId);
        $collection->setStoreId($storeId);

        $collection->joinField('qty',
            'cataloginventory/stock_item',
            'qty',
            'product_id=entity_id',
            '{{table}}.stock_id=' . Mage::getModel('cataloginventory/stock')->getId(),
            'left')
            ->getSelect()
            ->columns('entity_id AS id');

        /* allow to apply custom filters */
        Mage::dispatchEvent('webpos_catalog_product_collection_filter', array('collection' => $collection));

        $pageNumber = $this->getRequest()->getPageNumber();
        if ($pageNumber != abs($pageNumber)) {
            $this->_critical(self::RESOURCE_COLLECTION_PAGING_ERROR);
        }

        $pageSize = $this->getRequest()->getPageSize();
        if ($pageSize) {
            if ($pageSize != abs($pageSize) || $pageSize > self::PAGE_SIZE_MAX) {
                $this->_critical(self::RESOURCE_COLLECTION_PAGING_LIMIT_ERROR);
            }
        }


        $showOutOfStock = $this->getRequest()->getParam('show_out_stock');
        if (!$showOutOfStock) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }

        /* @var Varien_Data_Collection_Db $customerCollection */

        $this->_applyFilter($collection);
        $this->_applyFilterOr($collection);

        //$this->_applyFilterTo($collection);

        /*Search product by barcode in barcode sucess table*/
        if(Mage::helper('core')->isModuleEnabled('Magestore_Barcodesuccess') && !$collection->getSize() && $this->getRequest()->getQuery(Magestore_Webpos_Model_Api2_Abstract::QUERY_PARAM_OR_FILTER)){
            /*Filter in barcode attribute table*/
            $filter = $this->getRequest()->getQuery(Magestore_Webpos_Model_Api2_Abstract::QUERY_PARAM_OR_FILTER);
            $realkeywords = explode("fixbug*bugfix", $filter[Magestore_Webpos_Model_Api2_Abstract::FIRST_INDEX]['like']);
            if(!empty($realkeywords)){
                $filter[Magestore_Webpos_Model_Api2_Abstract::FIRST_INDEX]['like'] = implode('', $realkeywords);
            }
            $keyword = trim($filter[Magestore_Webpos_Model_Api2_Abstract::FIRST_INDEX]['like'],"%");
            $barcodeProduct = Mage::getModel('barcodesuccess/barcode')->getCollection()->addFieldToFilter('barcode',$keyword);
            if($barcodeProduct->getSize()){
                $data = $barcodeProduct->getData();
                $productId = $data[Magestore_Webpos_Model_Api2_Abstract::FIRST_INDEX]['product_id'];
                $collection = Mage::getModel('catalog/product')->getCollection();
                $collection->addAttributeToFilter('status', 1)
                    ->addAttributeToFilter('type_id', array('in' => $this->getProductTypeIds()))
                    ->addAttributeToFilter(array(
                        array('attribute' => 'webpos_visible', 'is' => new \Zend_Db_Expr('NULL'), 'left'),
                        array('attribute' => 'webpos_visible', 'eq' => Magestore_Webpos_Model_Source_Entity_Attribute_Source_Boolean::VALUE_YES, 'left'),
                    ), '', 'left');
                $collection->joinField('qty',
                    'cataloginventory/stock_item',
                    'qty',
                    'product_id=entity_id',
                    '{{table}}.stock_id=' . Mage::getModel('cataloginventory/stock')->getId(),
                    'left')
                    ->getSelect()
                    ->columns('entity_id AS id');
                $collection->addAttributeToFilter('entity_id',$productId);

            }
        }

        if(!$collection->getSize() && $this->getRequest()->getQuery(Magestore_Webpos_Model_Api2_Abstract::QUERY_PARAM_OR_FILTER)){
            $filter = $this->getRequest()->getQuery(Magestore_Webpos_Model_Api2_Abstract::QUERY_PARAM_OR_FILTER);
            $realkeywords = explode("fixbug*bugfix", $filter[Magestore_Webpos_Model_Api2_Abstract::FIRST_INDEX]['like']);
            if(!empty($realkeywords)){
                $filter[Magestore_Webpos_Model_Api2_Abstract::FIRST_INDEX]['like'] = implode('', $realkeywords);
            }
            $keyword = trim($filter[Magestore_Webpos_Model_Api2_Abstract::FIRST_INDEX]['like'],"%");
            $barcodeAttr = Mage::helper('webpos')->getStoreConfig('webpos/product_search/barcode', $storeId);
            $barcodeAttr = ($barcodeAttr)?$barcodeAttr:'sku';
            $childs = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter($barcodeAttr, $keyword);
            if($childs->getSize() > 0){
                $child_id = $childs->getFirstItem()->getId();
                $parent_ids = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($child_id);
                if(!empty($parent_ids)){
                    $collection = Mage::getModel('catalog/product')->getCollection();
                    $collection->addAttributeToFilter('status', 1)
                        ->addAttributeToFilter('entity_id', array('in' => $parent_ids))
                        ->addAttributeToFilter('type_id', array('in' => $this->getProductTypeIds()))
                        ->addAttributeToFilter(array(
                            array('attribute' => 'webpos_visible', 'is' => new \Zend_Db_Expr('NULL'), 'left'),
                            array('attribute' => 'webpos_visible', 'eq' => Magestore_Webpos_Model_Source_Entity_Attribute_Source_Boolean::VALUE_YES, 'left'),
                        ), '', 'left');
                    $collection->joinField('qty',
                        'cataloginventory/stock_item',
                        'qty',
                        'product_id=entity_id',
                        '{{table}}.stock_id=' . Mage::getModel('cataloginventory/stock')->getId(),
                        'left')
                        ->getSelect()
                        ->columns('entity_id AS id');
                }
            }
        }

        $result['total_count'] = $collection->getSize();
        $collection->setCurPage($pageNumber)->setPageSize($pageSize);
        $collection->load();
        $collection->addCategoryIds();


        $products = array();
        foreach ($collection as $productModel) {
            $formatCategories = array();
            $categories = $productModel->getCategoryIds();
            foreach ($categories as $category) {
                $formatCategories[] = "'" . $category . "'";
            }
            $productModel = Mage::getModel('webpos/catalog_product')->load($productModel->getId());
            $stockItem = $productModel->getStockItem();
            $item = $productModel->getData();
            $item['category_ids'] = implode(' ', $formatCategories);
            $item['minimum_qty'] = $stockItem->getMinSaleQty();
            $item['maximum_qty'] = $stockItem->getMaxSaleQty();
            $item['qty_increment'] = $stockItem->getQtyIncrements();
            $item['json_config'] = null;
            $item['config_options'] = null;
            $item['price_config'] = null;
            $item['custom_options'] = null;
            $item['grouped_options'] = null;
            $item['bundle_options'] = null;
            $item['id'] = $productModel->getEntityId();
            if ($this->getRequest()->getParam('status') == 'sync') {
                $item['barcode_options'] = $productModel->getBarcodeOptions();
                $item['barcode_string'] = $productModel->getBarcodeString();
                $item['search_string'] = $productModel->getSearchString();
                $item['json_config'] = $productModel->getJsonConfig();
                $item['config_options'] = $productModel->getConfigOptions();
                $item['price_config'] = $productModel->getPriceConfig();


                if ($productModel->getOptions()) {
                    foreach ($productModel->getOptions() as $option) {
                        if ($option->getType() === 'drop_down' || $option->getType() === 'radio'
                            || $option->getType() === 'checkbox' || $option->getType() === 'multiple'
                        ) {
                            $values = $option->getValues();
                            $valueArray = array();
                            foreach ($values as $value) {
                                $valueArray[] = $value->getData();
                            }
                            $option->setData('values', $valueArray);
                        }
                        if ($option->getData('is_require')) {
                            $option->setData('is_require', true);
                        } else {
                            $option->setData('is_require', false);
                        }
                        $item['custom_options'][] = $option->getData();
                    }
                } else {
                    $item['custom_options'] = null;
                }
                if (is_array($productModel->getGroupedOptions())) {
                    $item['grouped_options'] = array_values($productModel->getGroupedOptions());
                } else {
                    $item['grouped_options'] = null;
                }
                $item['bundle_options'] = $productModel->getBundleOptions();
                if (is_array($productModel->getBundleOptions())) {
                    $item['bundle_options'] = array_values($productModel->getBundleOptions());
                } else {
                    $item['bundle_options'] = null;
                }
            }

            if ($productModel->getCustomercreditValue()) {
                $item['customercredit_value'] = $productModel->getCustomercreditValue();
            }

            if ($productModel->getStorecreditType()) {
                $item['storecredit_type'] = $productModel->getStorecreditType();
            }

            if ($productModel->getStorecreditRate()) {
                $item['storecredit_rate'] = $productModel->getStorecreditRate();
            }

            if ($productModel->getStorecreditMin()) {
                $item['storecredit_min'] = $productModel->getStorecreditMin();
            }

            if ($productModel->getStorecreditMax()) {
                $item['storecredit_max'] = $productModel->getStorecreditMax();
            }


            if ($productModel->hasOptions()) {
                $item['options'] = 1;
            } else {
                $item['options'] = 0;
            }

            /* Save barcode string into IndexDB*/
            if(Mage::helper('core')->isModuleEnabled('Magestore_Barcodesuccess')){
                $barcodeProducts = Mage::getModel('barcodesuccess/barcode')->getCollection()->addFieldToFilter('product_id',$productModel->getEntityId());
                $barcodes = array();
                foreach ($barcodeProducts as $barcodeProduct) {
                    $barcodes[] = $barcodeProduct->getData('barcode');
                }
                if(!isset($item['barcode_string'])){
                    $item['barcode_string'] = '';
                }
                $item['barcode_string'] .= ','.implode(',', $barcodes);
            }

            $item['available_qty'] = $productModel->getStockItem()->getQty();
            // $item['final_price'] = $productModel->getFinalPrice();

            $storeId = Mage::app()->getStore()->getId();
            $discountedPrice = Mage::getResourceModel('catalogrule/rule')->getRulePrice(
                Mage::app()->getLocale()->storeTimeStamp($storeId),
                Mage::app()->getStore($storeId)->getWebsiteId(),
                Mage::getSingleton('customer/session')->getCustomerGroupId(),
                $productModel->getId());

            if ($discountedPrice === false) { // if no rule applied for the product
                $item['final_price'] = $productModel->getFinalPrice();
            } else {
                $item['final_price'] = number_format($discountedPrice, 2);
            }

            if ($productModel->getImage() && $productModel->getImage() != 'no_selection') {
//                $item['image'] = $productMedia.$item['image'];
                $item['image'] = $productModel->getImageUrl();
            } else {
                $item['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) .
                    'magestore/webpos/catalog/category/image.jpg';
            }

            if ($item['stock_item']['is_in_stock'] || (isset($item['is_in_stock']) && ($item['is_in_stock'] == true))) {
                $item['isShowOutStock'] = 0;
            } else {
                $item['isShowOutStock'] = 1;
            }

            if (!$showOutOfStock && !$item['stock_item']['is_in_stock']) {
                $result['total_count'] = $result['total_count'] - 1;
            } else {
                $products[] = $item;
            }
        }

        $result['items'] = $products;
        return $result;

    }

    /**
     *
     */
    public function getProductOptionsInformation()
    {
        $productId = $this->getRequest()->getParam('id');
        $productModel = Mage::getModel('webpos/catalog_product')->load($productId);
        $item['json_config'] = $productModel->getJsonConfig();
        $item['config_options'] = $productModel->getConfigOptions();
        $item['price_config'] = $productModel->getPriceConfig();
        if ($productModel->getOptions()) {
            foreach ($productModel->getOptions() as $option) {
                if ($option->getType() === 'drop_down' || $option->getType() === 'radio'
                    || $option->getType() === 'checkbox' || $option->getType() === 'multiple'
                ) {
                    $values = $option->getValues();
                    $valueArray = array();
                    foreach ($values as $value) {
                        $valueArray[] = $value->getData();
                    }
                    $option->setData('values', $valueArray);
                }
                if ($option->getData('is_require')) {
                    $option->setData('is_require', true);
                } else {
                    $option->setData('is_require', false);
                }
                $item['custom_options'][] = $option->getData();
            }
        } else {
            $item['custom_options'] = null;
        }
        if (is_array($productModel->getGroupedOptions())) {
            $item['grouped_options'] = array_values($productModel->getGroupedOptions());
        } else {
            $item['grouped_options'] = null;
        }
        $item['bundle_options'] = $productModel->getBundleOptions();
        if (is_array($productModel->getBundleOptions())) {
            $item['bundle_options'] = array_values($productModel->getBundleOptions());
        } else {
            $item['bundle_options'] = null;
        }


        return $item;
    }


    /**
     * @param Varien_Data_Collection_Db $collection
     * @return $this
     */
    protected function _applyFilter(Varien_Data_Collection_Db $collection)
    {
        $filter = $this->getRequest()->getFilter();


        if (!$filter) {
            return $this;
        }
        if (!is_array($filter)) {
            $this->_critical(self::RESOURCE_COLLECTION_FILTERING_ERROR);
        }
        if (method_exists($collection, 'addAttributeToFilter')) {
            $methodName = 'addAttributeToFilter';
        } elseif (method_exists($collection, 'addFieldToFilter')) {
            $methodName = 'addFieldToFilter';
        } else {
            return $this;
        }

        foreach ($filter as $filterEntry) {
            if (isset($filterEntry['in'])) {
                return $this;
            }
            $attributeCode = $filterEntry['attribute'];
            unset($filterEntry['attribute']);

            if ($attributeCode != 'category_ids') {
                try {
                    $collection->$methodName($attributeCode, $filterEntry);
                } catch (Exception $e) {
                    $this->_critical(self::RESOURCE_COLLECTION_FILTERING_ERROR);
                }
            } else {
                $categoryId = preg_replace("/[^0-9]/", "", $filterEntry);
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $collection->addCategoryFilter($category)->addAttributeToSelect('*');
            }
        }

        return $this;
    }

    /**
     * get product type ids to support
     * @return array
     */
    public function getProductTypeIds()
    {
        $types = array('virtual', 'simple', 'grouped', 'bundle', 'configurable', 'customercredit');
        return $types;
    }


}
