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
class Magestore_Webpos_Model_Api2_Swatch_Rest_Admin_V1 extends Mage_Api2_Model_Resource
{

    protected function _retrieveCollection()
    {

        $dataCollection = array();
        return $dataCollection;;
        /*
         * note lay cac attribute http://prntscr.com/d7tqmu */
        //echo 'api /webpos/products/swatch/search'; die;
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        $store = $this->_getStore();
        $collection->setStoreId($store->getId());
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('image');
        $collection->addAttributeToSelect('path');
        $collection->addAttributeToSelect('parent_id');
        $collection->addAttributeToSelect('is_active');
        $categoryArray = array();
        foreach ($collection as $category) {
            $categoryNormalData = $category->getData();
            if ($category->getImageUrl()) {
                $categoryNormalData['image'] = $category->getImageUrl();
            } else {
                $categoryNormalData['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'magestore/webpos/catalog/category/image.jpg';
            }
            $categoryArray[] = $categoryNormalData;
        }
        return $categoryArray;

        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $productMedia = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product';
        $collection = Mage::getResourceModel('catalog/product_collection');
        $store = $this->_getStore();
        $collection->setStoreId($store->getId());
        $collection->addAttributeToSelect('*')->joinField('qty',
            'cataloginventory/stock_item',
            'qty',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left');
        ;
        $this->_applyCategoryFilter($collection);
        $this->_applyCollectionModifiers($collection);
        $products = $collection->load()->toArray();
        $dataCollection = array();
        foreach ($products as $item) {
            $item['available_qty'] = $item['qty'];
            $item['final_price'] = $item['price'];
            $item['image'] = $productMedia.$item['image'];
            if ($item['stock_item']['is_in_stock']) {
                $item['isShowOutStock'] = 0;
            } else {
                $item['isShowOutStock'] = 1;
            }

            $dataCollection[] = $item;
        }
        return $dataCollection;

        $swatchAttributeArray = array();
        $swatchArray = array();
        $collection = $this->attributeCollection->create();
        foreach ($collection as $attributeModel) {
            $isSwatch = $this->swatchHelper->isSwatchAttribute($attributeModel);
            if ($isSwatch) {
                $swatchAttributeArray[] = $attributeModel->getId();
                $attributeOptions = array();
                foreach ($attributeModel->getOptions() as $option) {
                    $attributeOptions[$option->getValue()] = $this->getUnusedOption($option);
                }
                $attributeOptionIds = array_keys($attributeOptions);
                $swatches = $this->swatchHelper->getSwatchesByOptionsId($attributeOptionIds);
                $data = array(
                    'attribute_id' => $attributeModel->getId(),
                    'attribute_code' => $attributeModel->getAttributeCode(),
                    'attribute_label' => $attributeModel->getStoreLabel(),
                    'swatches' => $swatches
                );
                $swatchArray[] = $data;
            }
        }
        $swatchInterface = $this->swatchResultInterface->create();
        $swatchInterface->setItems($swatchArray);
        $swatchInterface->setTotalCount(count($swatchArray));
        return $swatchInterface;
    }

//    public function dispatch()
//    {
//        switch ($this->getActionType() . $this->getOperation()) {
//            /* Create */
//            case self::ACTION_TYPE_ENTITY . self::OPERATION_CREATE:
//                // Creation of objects is possible only when working with collection
//                $this->_critical(self::RESOURCE_METHOD_NOT_IMPLEMENTED);
//                break;
//            case self::ACTION_TYPE_COLLECTION . self::OPERATION_CREATE:
//                // If no of the methods(multi or single) is implemented, request body is not checked
//                if (!$this->_checkMethodExist('_create') && !$this->_checkMethodExist('_multiCreate')) {
//                    $this->_critical(self::RESOURCE_METHOD_NOT_IMPLEMENTED);
//                }
//                // If one of the methods(multi or single) is implemented, request body must not be empty
//                $requestData = $this->getRequest()->getBodyParams();
//                if (empty($requestData)) {
//                    $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
//                }
//                // The create action has the dynamic type which depends on data in the request body
//                if ($this->getRequest()->isAssocArrayInRequestBody()) {
//                    $this->_errorIfMethodNotExist('_create');
//                    $filteredData = $this->getFilter()->in($requestData);
//                    if (empty($filteredData)) {
//                        $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
//                    }
//                    $newItemLocation = $this->_create($filteredData);
//                    $this->getResponse()->setHeader('Location', $newItemLocation);
//                } else {
//                    $this->_errorIfMethodNotExist('_multiCreate');
//                    $filteredData = $this->getFilter()->collectionIn($requestData);
//                    $this->_multiCreate($filteredData);
//                    $this->_render($this->getResponse()->getMessages());
//                    $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_MULTI_STATUS);
//                }
//                break;
//            /* Retrieve */
//            case self::ACTION_TYPE_ENTITY . self::OPERATION_RETRIEVE:
//                $this->_errorIfMethodNotExist('_retrieve');
//                $retrievedData = $this->_retrieve();
//                $filteredData  = $this->getFilter()->out($retrievedData);
//                $this->_render($filteredData);
//                break;
//            case self::ACTION_TYPE_COLLECTION . self::OPERATION_RETRIEVE:
//                $this->_errorIfMethodNotExist('_retrieveCollection');
//                $retrievedData = $this->_retrieveCollection();
////                $filteredData  = $this->getFilter()->collectionOut($retrievedData);
//                $this->_render($retrievedData);
//                break;
//            /* Update */
//            case self::ACTION_TYPE_ENTITY . self::OPERATION_UPDATE:
//                $this->_errorIfMethodNotExist('_update');
//                $requestData = $this->getRequest()->getBodyParams();
//                if (empty($requestData)) {
//                    $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
//                }
//                $filteredData = $this->getFilter()->in($requestData);
//                if (empty($filteredData)) {
//                    $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
//                }
//                $this->_update($filteredData);
//                break;
//            case self::ACTION_TYPE_COLLECTION . self::OPERATION_UPDATE:
//                $this->_errorIfMethodNotExist('_multiUpdate');
//                $requestData = $this->getRequest()->getBodyParams();
//                if (empty($requestData)) {
//                    $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
//                }
//                $filteredData = $this->getFilter()->collectionIn($requestData);
//                $this->_multiUpdate($filteredData);
//                $this->_render($this->getResponse()->getMessages());
//                $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_MULTI_STATUS);
//                break;
//            /* Delete */
//            case self::ACTION_TYPE_ENTITY . self::OPERATION_DELETE:
//                $this->_errorIfMethodNotExist('_delete');
//                $this->_delete();
//                break;
//            case self::ACTION_TYPE_COLLECTION . self::OPERATION_DELETE:
//                $this->_errorIfMethodNotExist('_multiDelete');
//                $requestData = $this->getRequest()->getBodyParams();
//                if (empty($requestData)) {
//                    $this->_critical(self::RESOURCE_REQUEST_DATA_INVALID);
//                }
//                $this->_multiDelete($requestData);
//                $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_MULTI_STATUS);
//                break;
//            default:
//                $this->_critical(self::RESOURCE_METHOD_NOT_IMPLEMENTED);
//                break;
//        }
//    }

}
