<?php

namespace Paynl\Payment\Observer;

use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockProcessor;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SubtractInventoryObserver implements ObserverInterface
{
    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var \Magento\CatalogInventory\Observer\ItemsForReindex
     */
    protected $itemsForReindex;


    /**
     * @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor
     */
    protected stockIndexerProcessor;

    /**
     * SubtractInventoryObserver constructor.
     * @param StockManagementInterface $stockManagement
     * @param StockProcessor $stockIndexerProcessor
     */
    public function __construct(
        StockManagementInterface $stockManagement,
        StockProcessor $stockIndexerProcessor
    ) {
        $this->stockManagement = $stockManagement;
        $this->stockIndexerProcessor = $stockIndexerProcessor;
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

            /**
             * Reindex items
             */
            $itemsForReindex = $this->stockManagement->registerProductsSale(
                $productQty,
                $order->getStore()->getWebsiteId()
            );

            $productIds = [];
            foreach ($itemsForReindex as $item) {
                $item->save();
                $productIds[] = $item->getProductId();
            }
            if (!empty($productIds)) {
                $this->stockIndexerProcessor->reindexList($productIds);
            }

            $order->setInventoryProcessed(true);
            return $this;
        }
    }
}
