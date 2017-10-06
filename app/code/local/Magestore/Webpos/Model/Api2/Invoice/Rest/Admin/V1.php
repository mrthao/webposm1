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
 * API2 for invoice order
 *
 * @author     Magestore Core Team
 */
class Magestore_Webpos_Model_Api2_Invoice_Rest_Admin_V1 extends Magestore_Webpos_Model_Api2_Abstract
{
    /**
     *
     */
    const OPERATION_CREATE_INVOICE = 'save';

    public function dispatch()
    {
        switch ($this->getActionType()) {
            case self::OPERATION_CREATE_INVOICE:
                $params = $this->getRequest()->getBodyParams();
                $result = $this->saveInvoice($params);
                $this->_render($result);
                $this->getResponse()->setHttpResponseCode(Mage_Api2_Model_Server::HTTP_OK);
                break;
        }
    }

    public function createInvoice($orderIncrementId, $itemsQty = array(), $comment = null, $email = false, $includeComment = false){
        $api = Mage::getModel('sales/order_invoice_api');
        $api->create($orderIncrementId, $itemsQty, $comment, $email, $includeComment);
    }

    /**
     * Perform persist operations for one entity
     *
     * @param array $params
     * @return array
     */
    public function saveInvoice($params){
        $helper = Mage::helper('webpos/order');
        if (isset($params['entity'])) {
            $entity = $params['entity'];
            $emailSent = $entity['emailSent'];
            $orderId = $entity['orderId'];
            $order = Mage::getModel('sales/order')->load($orderId);
            $data = $this->prepareInvoice($entity);
            $invoiceItems = isset($data['invoice']['items']) ? $data['invoice']['items'] : array();
            $invoice =  Mage::getModel('sales/service_order', $order)->prepareInvoice($invoiceItems);

            $this->saveInvoiceNote($invoice, $data);
            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();

            if (!empty($data['invoice']['do_shipment']) || (int)$invoice->getOrder()->getForcedShipmentWithInvoice()) {
                $shipment = $this->_prepareShipment($invoice);
                if ($shipment) {
                    $transactionSave->addObject($shipment);
                }
            }

            if ($emailSent) {
                $this->sendEmail($invoice);
            }
            $result = $helper->getAllOrderInfo($order);
            return $result;
        }
    }

    /**
     * @param object $invoice
     */
    public function sendEmail($invoice) {
        $invoice->sendEmail();
        $historyItem = Mage::getResourceModel('sales/order_status_history_collection')
            ->getUnnotifiedForInstance($invoice, Mage_Sales_Model_Order_Invoice::HISTORY_ENTITY_NAME);
        if ($historyItem) {
            $historyItem->setIsCustomerNotified(1);
            $historyItem->save();
        }
    }

    /**
     * @param object $invoice
     * @param array $data
     */
    public function saveInvoiceNote($invoice, $data) {
        if (!empty($data['invoice']['comment_text'])) {
            $invoice->addComment(
                $data['invoice']['comment_text'],
                isset($data['invoice']['comment_customer_notify']),
                isset($data['invoice']['is_visible_on_front'])
            );
            $invoice->setCustomerNote($data['invoice']['comment_text']);
            $invoice->setCustomerNoteNotify(isset($data['invoice']['comment_customer_notify']));
        }
    }

    /**
     * prepare invoice
     *
     * @param array $params
     */
    protected function prepareInvoice($params){
        $data = array();
        $items = $params['items'];
        $orderId = $params['orderId'];
        if(count($items>0) && $orderId) {
            $data['order_id'] = $orderId;
            $invoice = array();
            foreach ($items as $item){
                $invoice['items'][$item['orderItemId']] = $item['qty'];
            }
            $comments = $params['comments'];
            if(count($comments) && $comment = $comments[0]){
                $invoice['comment_text'] = $comment['comment'];
                if($invoice['send_email'])
                    $invoice['comment_customer_notify'] = 1;
            }
            $data['invoice'] = $invoice;
            return $data;
        }
        return null;
    }

    /**
     * @param object $invoice
     * @param object $order
     * @return object
     */
    protected function _prepareShipment($invoice, $order) {
        $savedQtys = $this->_getItemQtys($order);
        $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($savedQtys);
        if (!$shipment->getTotalQty()) {
            return false;
        }
        $shipment->register();
        return $shipment;
    }

    /**
     * @param object $order
     * @return array
     */
    protected function _getItemQtys($order) {
        $savedQtys = array();
        $_order_items = $order->getAllItems();
        foreach ($_order_items as $_order_item) {
            $savedQtys[$_order_item->getId()] = (int)$_order_item->getQtyOrdered();
        }
        return $savedQtys;
    }

}
