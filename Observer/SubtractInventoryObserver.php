<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite\Proxy as GetAssignedStockIdForWebsiteProxy;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface\Proxy as ReservationBuilderInterfaceProxy;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface\Proxy as AppendReservationsInterfaceProxy;

class SubtractInventoryObserver implements ObserverInterface
{
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var GetAssignedStockIdForWebsite
     */
    private $getAssignedStockIdForWebsite;

    /**
     * @var ReservationBuilderInterface
     */
    private $reservationBuilder;

    /**
     * @var AppendReservationsInterface
     */
    private $appendReservations;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * SubtractInventoryObserver constructor.
     * @param StoreRepositoryInterface $storeRepository
     * @param GetAssignedStockIdForWebsiteProxy $getAssignedStockIdForWebsite
     * @param ReservationBuilderInterfaceProxy $reservationBuilder
     * @param AppendReservationsInterfaceProxy $appendReservations
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        StoreRepositoryInterface $storeRepository,
        GetAssignedStockIdForWebsiteProxy $getAssignedStockIdForWebsite,
        ReservationBuilderInterfaceProxy $reservationBuilder,
        AppendReservationsInterfaceProxy $appendReservations,
        StockRegistryInterface $stockRegistry
    ) {
        $this->storeRepository = $storeRepository;
        $this->getAssignedStockIdForWebsite = $getAssignedStockIdForWebsite;
        $this->reservationBuilder = $reservationBuilder;
        $this->appendReservations = $appendReservations;
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

            if (!class_exists(\Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite::class) && class_exists(\Magento\InventoryReservationsApi\Model\ReservationBuilderInterface::class) && class_exists(\Magento\InventoryReservationsApi\Model\AppendReservationsInterface::class)) {
                $stockId = $this->getAssignedStockIdForWebsite->execute($websiteCode);
                $reservations = [];

                foreach ($order->getAllItems() as $item) {
                    $itemData = $item->getData();

                    $itemQty = $itemData['qty_ordered'] ?? null;
                    $itemSku = $itemData['sku'] ?? null;

                    if (!empty($itemQty) && !empty($itemSku)) {
                        $reservations[] = $this->reservationBuilder
                            ->setSku($itemSku)
                            ->setQuantity(-$itemQty)
                            ->setStockId($stockId)
                            ->build();
                    }
                }

                $this->appendReservations->execute($reservations);
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
