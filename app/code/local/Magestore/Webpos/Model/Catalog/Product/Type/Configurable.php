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
class Magestore_Webpos_Model_Catalog_Product_Type_Configurable extends Mage_Catalog_Model_Product
{

    /**
     * @param null $product
     * @return mixed
     */
    public function getUsedProductCollection($product = null)
    {
        $collection = Mage::getResourceModel('catalog/product_type_configurable_product_collection')
            ->setFlag('require_stock_items', true)
            ->setFlag('product_children', true)
            ->setProductFilter($this->getProduct($product));
        if (!is_null($this->getStoreFilter($product))) {
            $collection->addStoreFilter($this->getStoreFilter($product));
        }

        return $collection;
    }

    /**
     * @return mixed
     */
    public function getFirstPriceConfig()
    {
        foreach ($this->getAllowProducts() as $product) {
            if ($product->getFinalPrice()) {
                return $product->getFinalPrice();
            }
        }
    }
    /**
     * Composes configuration for js
     *
     * @return string
     */
    public function getJsonConfig()
    {
        if ($this->getData('type_id') != Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE)
            return;
        $config = array();
        return  Zend_Json::encode($config);

        $store = Mage::app()->getStore();

        $currentProduct = $this->getProduct();

        /* @var Mage_Catalog_Helper_Product_Configuration $helper*/
        $helper = Mage::helper('catalog/product_configuration');
        $options = $helper->getOptions($currentProduct, $this->getAllowProducts());


        $config = array(
            'template' => str_replace('%s', '<%- data.price %>', $store->getCurrentCurrency()->getOutputFormat()),
            'optionPrices' => $this->getOptionPrices(),
            'prices' => array(
                'oldPrice' => array(
                    'amount' => $this->registerJsPrice($currentProduct->getPrice()),
                ),
                'basePrice' => array(
                    'amount' => $this->registerJsPrice(
                        $currentProduct->getBasePrice()
                    ),
                ),
                'finalPrice' => array(
                    'amount' => $this->registerJsPrice($currentProduct->getFinalPrice()),
                ),
            ),
            'productId' => $currentProduct->getId(),
            'chooseText' => Mage::helper('webpos')->__('Choose an Option...'),
//            'images' => isset($options['images']) ? $options['images'] : [],
//            'index' => isset($options['index']) ? $options['index'] : [],
        );


        if ($currentProduct->hasPreconfiguredValues() && !empty($attributesData['defaultValues'])) {
            $config['defaultValues'] = $attributesData['defaultValues'];
        }

        $config = array_merge($config, $this->getAdditionalConfig());

        $str = Zend_Json::encode($config);
        return $str;
    }

    /**
     * Returns additional values for js config, con be overridden by descendants
     *
     * @return array
     */
    public function getAdditionalConfig()
    {
        return array();
    }


    /**
     * @param $price
     * @return mixed
     */
    public function registerJsPrice($price)
    {
        return str_replace(',', '.', $price);
    }

    /**
     * @return array
     */
    public function getOptionPrices()
    {
        $prices = array();
        foreach ($this->getAllowProducts() as $product) {
            $prices[$product->getId()] =
                array(
                    'oldPrice' => array(
                        'amount' => $this->registerJsPrice(
                            Mage::getModel('catalog/product')->load($product->getId())->getPrice()
                        ),
                    ),
                    'basePrice' => array(
//                        'amount' => $this->registerJsPrice(
//                            $priceInfo->getPrice('final_price')->getAmount()->getBaseAmount()
//                        ),
                        'amount' => Mage::getModel('catalog/product')->load($product->getId())->getFinalPrice(),
                    ),
                    'finalPrice' => array(
                        'amount' => $this->registerJsPrice(
                            Mage::getModel('catalog/product')->load($product->getId())->getFinalPrice()
                        ),
                    )
                );
        }
        return $prices;
    }
    /**
     * Get Allowed Products
     *
     * @return array
     */
    public function getAllowProducts()
    {
        $products = array();
        $currentProduct = $this->getProduct();
        $allProducts = $currentProduct->getTypeInstance()->getUsedProducts($currentProduct, null);
        foreach ($allProducts as $product) {
            $products[] = $product;
        }
        return $products;
    }


}
