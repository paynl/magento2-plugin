<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SubtractInventoryObserver implements ObserverInterface
{

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * SubtractInventoryObserver constructor.
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Subtract items qtys from stock related with uncancel products.
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\Paymentmethod) {
            $productQty = $observer->getEvent()->getProductQty();

            if ($order->getInventoryProcessed()) {
                return $this;
            }

            foreach ($order->getAllItems() as $item) {
                $itemData = $item->getData();

                $itemId = $itemData['product_id'] ?? null;
                $itemQty = $itemData['qty_ordered'] ?? null;
                $itemSku = $itemData['sku'] ?? null;

                if (!empty($itemId) && !empty($itemQty) && !empty($itemSku)) {
                    $stockItem = $this->stockRegistry->getStockItem($itemId);
                    $currentQty = (int) $stockItem->getQty();
                    $newQty = max(0, $currentQty - $itemQty);
                    $stockItem->setQty($newQty);
                    $this->stockRegistry->updateStockItemBySku($itemSku, $stockItem);
                }
            }

            $order->setInventoryProcessed(true);
            return $this;
        }
    }
}
