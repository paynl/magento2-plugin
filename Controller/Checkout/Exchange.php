<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Controller\Checkout;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

/**
 * Description of Index
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Exchange extends \Magento\Framework\App\Action\Action {
	/**
	 *
	 * @var \Paynl\Payment\Model\Config
	 */
	private $config;

	/**
	 *
	 * @var \Magento\Sales\Model\OrderFactory
	 */
	private $orderFactory;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	/**
	 *
	 * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
	 */
	private $orderSender;

	/**
	 *
	 * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
	 */
	private $invoiceSender;

	/**
	 * @var \Magento\Framework\Controller\Result\Raw
	 */
	private $result;

	/**
	 * @var OrderRepository
	 */
	private $orderRepository;

	private $paynlConfig;

	/**
	 * Exchange constructor.
	 *
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Paynl\Payment\Model\Config $config
	 * @param \Magento\Sales\Model\OrderFactory $orderFactory
	 * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
	 * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
	 * @param \Psr\Log\LoggerInterface $logger
	 * @param \Magento\Framework\Controller\Result\Raw $result
	 */
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Paynl\Payment\Model\Config $config,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
		\Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\Controller\Result\Raw $result,
		OrderRepository $orderRepository,
        \Paynl\Payment\Model\Config $paynlConfig
	) {
		$this->result          = $result;
		$this->config          = $config;
		$this->orderFactory    = $orderFactory;
		$this->orderSender     = $orderSender;
		$this->invoiceSender   = $invoiceSender;
		$this->logger          = $logger;
		$this->orderRepository = $orderRepository;
		$this->paynlConfig     = $paynlConfig;

		parent::__construct( $context );
	}

	public function execute() {
		$skipFraudDetection = $this->config->isSkipFraudDetection();
		\Paynl\Config::setApiToken( $this->config->getApiToken() );

		$params = $this->getRequest()->getParams();
		if ( ! isset( $params['order_id'] ) ) {
			$this->logger->critical( 'Exchange: order_id is not set in the request', $params );

			return $this->result->setContents( 'FALSE| order_id is not set in the request' );
		}

		try {
			$transaction = \Paynl\Transaction::get( $params['order_id'] );
		} catch ( \Exception $e ) {
			$this->logger->critical( $e, $params );

			return $this->result->setContents( 'FALSE| Error fetching transaction. ' . $e->getMessage() );
		}

		if ( $transaction->isPending() ) {
			return $this->result->setContents( "TRUE| Ignoring pending" );
		}

		$orderEntityId = $transaction->getExtra3();
		/** @var Order $order */
		$order = $this->orderRepository->get( $orderEntityId );

		if ( empty( $order ) ) {
			$this->logger->critical( 'Cannot load order: ' . $orderEntityId );

			return $this->result->setContents( 'FALSE| Cannot load order' );
		}
		if ( $order->getTotalDue() <= 0 ) {
			$this->logger->debug( 'Total due <= 0, so iam not touching the status of the order: ' . $orderEntityId );

			return $this->result->setContents( 'TRUE| Total due <= 0, so iam not touching the status of the order' );
		}

		if ( $transaction->isPaid() ) {
			$message = "PAID";
			if ( $order->isCanceled() ) {
				try {
					$this->uncancel( $order );
				} catch ( LocalizedException $e ) {
					return $this->result->setContents( 'FALSE| Cannot un-cancel order: ' . $e->getMessage() );
				}
				$message .= " order was uncanceled";
			}
			/** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
			$payment = $order->getPayment();
			$payment->setTransactionId(
				$transaction->getId()
			);

			$payment->setPreparedMessage( 'Pay.nl - ' );
			$payment->setIsTransactionClosed(
				0
			);
            $paidAmount = $transaction->getPaidCurrencyAmount();

            if(!$this->paynlConfig->isAlwaysBaseCurrency()){
                if($order->getBaseCurrencyCode() != $order->getOrderCurrencyCode()){
                    // we can only register the payment in the base currency
                    $paidAmount = $order->getBaseGrandTotal();
                }
            }

			$payment->registerCaptureNotification(
                $paidAmount, $skipFraudDetection
			);

			$this->orderRepository->save( $order );

			// notify customer
			$invoice = $payment->getCreatedInvoice();
			if ( $invoice && ! $order->getEmailSent() ) {
				$this->orderSender->send( $order );
				$order->addStatusHistoryComment(
					__( 'New order email sent' )
				)->setIsCustomerNotified(
					true
				)->save();
			}
			if ( $invoice && ! $invoice->getEmailSent() ) {
				$this->invoiceSender->send( $invoice );

				$order->addStatusHistoryComment(
					__( 'You notified customer about invoice #%1.',
						$invoice->getIncrementId() )
				)->setIsCustomerNotified(
					true
				)->save();

			}

			return $this->result->setContents( "TRUE| " . $message );

		} elseif ( $transaction->isCanceled() ) {
			if ( $this->config->isNeverCancel() ) {
				return $this->result->setContents( "TRUE| Not Canceled because never cancel is enabled" );
			}
			if ( $order->getState() == 'holded' ) {
				$order->unhold();
			}

			$order->cancel();
			$order->addStatusHistoryComment( __( 'Pay.nl canceled the order' ) );
			$this->orderRepository->save( $order );

			return $this->result->setContents( "TRUE| CANCELED" );
		}

	}

	private function uncancel( \Magento\Sales\Model\Order $order ) {
		if ( $order->isCanceled() ) {
			$state           = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
			$productStockQty = [];
			foreach ( $order->getAllVisibleItems() as $item ) {
				$productStockQty[ $item->getProductId() ] = $item->getQtyCanceled();
				foreach ( $item->getChildrenItems() as $child ) {
					$productStockQty[ $child->getProductId() ] = $item->getQtyCanceled();
					$child->setQtyCanceled( 0 );
					$child->setTaxCanceled( 0 );
					$child->setDiscountTaxCompensationCanceled( 0 );
				}
				$item->setQtyCanceled( 0 );
				$item->setTaxCanceled( 0 );
				$item->setDiscountTaxCompensationCanceled( 0 );
				$this->_eventManager->dispatch( 'sales_order_item_uncancel', [ 'item' => $item ] );
			}
			$this->_eventManager->dispatch(
				'sales_order_uncancel_inventory',
				[
					'order'       => $order,
					'product_qty' => $productStockQty
				]
			);
			$order->setSubtotalCanceled( 0 );
			$order->setBaseSubtotalCanceled( 0 );
			$order->setTaxCanceled( 0 );
			$order->setBaseTaxCanceled( 0 );
			$order->setShippingCanceled( 0 );
			$order->setBaseShippingCanceled( 0 );
			$order->setDiscountCanceled( 0 );
			$order->setBaseDiscountCanceled( 0 );
			$order->setTotalCanceled( 0 );
			$order->setBaseTotalCanceled( 0 );
			$order->setState( $state );
			$order->setStatus( $state );

			$order->addStatusHistoryComment( __( 'Pay.nl Uncanceled order' ), false );

			$this->_eventManager->dispatch( 'order_uncancel_after', [ 'order' => $order ] );
		} else {
			throw new LocalizedException( __( 'We cannot un-cancel this order.' ) );
		}

		return $order;
	}
}