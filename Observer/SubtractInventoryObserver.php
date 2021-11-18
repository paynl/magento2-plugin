<?php

namespace Paynl\Payment\Observer;

use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockProcessor;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SubtractInventoryObserver constructor.
     * @param StockManagementInterface $stockManagement
     * @param StockProcessor $stockIndexerProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        StockManagementInterface $stockManagement,
        StockProcessor $stockIndexerProcessor,
        LoggerInterface $logger
    ) {
        $this->stockManagement = $stockManagement;
        $this->stockIndexerProcessor = $stockIndexerProcessor;
        $this->logger = $logger;
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
