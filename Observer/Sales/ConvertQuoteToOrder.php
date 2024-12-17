<?php

namespace Marketplacer\SellerApi\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Marketplacer\SellerApi\Api\Data\OrderItemInterface as SellerOrderItemInterface;
use Marketplacer\SellerApi\Model\Order\SellerDataPreparer;
use Marketplacer\SellerApi\Api\Data\OrderInterface as SellerOrderInterface;

class ConvertQuoteToOrder implements ObserverInterface
{
    /**
     * ConvertQuoteToOrder constructor.
     * @param SellerDataPreparer $sellerDataPreparer
     */
    public function __construct(
        private readonly SellerDataPreparer $sellerDataPreparer
    ) {
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        /**
         * @var \Magento\Quote\Model\Quote $quote
         * @var \Magento\Sales\Model\Order $order
         */

        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();

        $items = $quote->getAllVisibleItems();
        $sellerIds = $this->sellerDataPreparer->getSellerIdsByQuoteItems($items);
        $sellerNames = $this->sellerDataPreparer->getSellerNamesByIds(
            $sellerIds,
            $order->getStoreId()
        );

        $order->setData(SellerOrderInterface::SELLER_IDS, implode(',', $sellerIds));
        $order->setData(SellerOrderInterface::SELLER_NAMES, implode(', ', $sellerNames));

        $this->addShippingInfoToOrderLineItems($order);

        return $this;
    }

    /**
     * @param OrderInterface $order
     * @return void
     */
    private function addShippingInfoToOrderLineItems(OrderInterface $order): void
    {
        $orderData = [
            SellerOrderItemInterface::BASE_SHIPPING_FEE =>
                (float)$order->getBaseShippingInclTax(),
            SellerOrderItemInterface::SHIPPING_FEE =>
                (float)$order->getShippingInclTax(),
            SellerOrderItemInterface::BASE_SHIPPING_TAX_AMOUNT =>
                (float)$order->getBaseShippingTaxAmount(),
            SellerOrderItemInterface::SHIPPING_TAX_AMOUNT =>
                (float)$order->getShippingTaxAmount(),
        ];

        $countItems = (int)$order->getTotalQtyOrdered();

        $forItem = [];
        foreach ($orderData as $field => $value) {
            $forItem[$field] = round($value * 100 / $countItems,5,PHP_ROUND_HALF_DOWN) / 100;
        }

        /** @var OrderItemInterface[] $items */
        $items = $order->getAllVisibleItems();

        foreach ($items as $orderItem) {
            foreach ($forItem as $key => $value) {
                $fieldValue = round(
                    $value * $orderItem->getQtyOrdered(),
                    5,
                    PHP_ROUND_HALF_DOWN
                );
                $orderItem->setData($key, round($fieldValue, 4,PHP_ROUND_HALF_DOWN));

                $orderData[$key] = round($orderData[$key] - $fieldValue, 4);
            }
        }


        if ($orderData[SellerOrderItemInterface::BASE_SHIPPING_FEE] > 0 || $orderData[SellerOrderItemInterface::BASE_SHIPPING_TAX_AMOUNT] > 0) {
            $firstItem = $items[0];
            foreach ($orderData as $key => $value) {
                $fieldValue = round($firstItem->getData($key) + $value, 4);
                $firstItem->setData($key, $fieldValue);
            }
        }
    }
}
