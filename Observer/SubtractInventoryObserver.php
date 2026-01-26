<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;

class SubtractInventoryObserver implements ObserverInterface
{
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var StockRegistryInterfaceX
     */
    private $stockRegistry;

    /**
     * SubtractInventoryObserver constructor.
     * @param StoreRepositoryInterface $storeRepository
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        StoreRepositoryInterface $storeRepository,
        StockRegistryInterface $stockRegistry
    ) {
        $this->storeRepository = $storeRepository;
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

            if ($order->getInventoryProcessed()) {
                return $this;
            }

            $storeId = $order->getStoreId();
            $website = $this->storeRepository->getById($storeId)->getWebsite();
            $websiteCode = $website->getCode();
            $websiteId = $website->getId();

            if (class_exists(GetAssignedStockIdForWebsite::class) && interface_exists(ReservationBuilderInterface::class) && interface_exists(AppendReservationsInterface::class)) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                $getAssignedStockIdForWebsite = $objectManager->get(GetAssignedStockIdForWebsite::class);
                $reservationBuilder = $objectManager->get(ReservationBuilderInterface::class);
                $appendReservations = $objectManager->get(AppendReservationsInterface::class);

                $stockId = $getAssignedStockIdForWebsite->execute($websiteCode);
                $reservations = [];

                foreach ($order->getAllItems() as $item) {
                    $itemData = $item->getData();

                    $itemQty = $itemData['qty_ordered'] ?? null;
                    $itemSku = $itemData['sku'] ?? null;

                    if (!empty($itemQty) && !empty($itemSku)) {
                        $reservations[] = $reservationBuilder
                            ->setSku($itemSku)
                            ->setQuantity(-$itemQty)
                            ->setStockId($stockId)
                            ->build();
                    }
                }
                $appendReservations->execute($reservations);
            } else {
                foreach ($order->getAllItems() as $item) {
                    $itemData = $item->getData();

                    $itemQty = $itemData['qty_ordered'] ?? null;
                    $itemSku = $itemData['sku'] ?? null;

                    $stockItem = $this->stockRegistry->getStockItemBySku($itemSku, $websiteId);
                    $currentQty = (float) $stockItem->getQty();
                    $stockItem->setQty($currentQty - $itemQty);
                    $stockItem->setIsInStock((($currentQty - $itemQty) > 0) ? 1 : 0);
                    $this->stockRegistry->updateStockItemBySku($itemSku, $stockItem);
                }
            }

            $order->setInventoryProcessed(true)->save();
            return $this;
        }
    }
}
