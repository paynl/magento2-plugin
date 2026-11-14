<?php

namespace Paynl\Payment\Test\Unit\Model\Paymentmethod;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\Store;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;
use PHPUnit\Framework\TestCase;

class PaylinkTest extends TestCase
{
    /**
     * @return void
     */
    public function testInitializeAddsCommentsAndKeepsConfiguredStatusWhenCustomerEmailIsEmpty(): void
    {
        $statusComments = [];

        $order = $this->createStub(Order::class);
        $order->method('getStore')->willReturn($this->createStub(Store::class));
        $order->method('getStoreId')->willReturn(1);
        $order->method('getCustomerEmail')->willReturn('');
        $order->method('getPayment')->willReturn($this->createStub(Payment::class));
        $order->method('addStatusHistoryComment')->willReturnCallback(
            function ($comment, $status) use (&$statusComments, $order) {
                $statusComments[] = (string) $comment;

                return $order;
            }
        );
        $order->method('save')->willReturnSelf();
        $order->method('getCustomerName')->willReturn('');
        $order->method('getIncrementId')->willReturn('1');
        $order->method('getEntityId')->willReturn(1);
        $order->method('getBillingAddress')->willReturn(null);
        $order->method('getShippingAddress')->willReturn(null);
        $order->method('getIsVirtual')->willReturn(true);
        $order->method('getCreatedAtFormatted')->willReturn('');
        $order->method('getIsNotVirtual')->willReturn(false);
        $order->method('getEmailCustomerNote')->willReturn('');
        $order->method('getFrontendStatusLabel')->willReturn('');

        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static function ($path, $scopeType = null, $scopeId = null): string {
                return $path === 'payment/paynl_payment_paylink/send_paylink_email' ? '1' : '';
            }
        );

        $locale = $this->createStub(Resolver::class);
        $locale->method('getLocale')->willReturn('en_US');

        $paymentData = $this->createStub(PaymentData::class);
        $paymentData->method('getInfoBlockHtml')->willReturn('');

        $addressRenderer = $this->createStub(Renderer::class);
        $addressRenderer->method('format')->willReturn('');

        $transport = $this->createStub(\Magento\Framework\Mail\TransportInterface::class);

        $transportBuilder = $this->createStub(TransportBuilder::class);
        $transportBuilder->method('setTemplateIdentifier')->willReturnSelf();
        $transportBuilder->method('setTemplateOptions')->willReturnSelf();
        $transportBuilder->method('setTemplateVars')->willReturnSelf();
        $transportBuilder->method('setFrom')->willReturnSelf();
        $transportBuilder->method('addTo')->willReturnSelf();
        $transportBuilder->method('setReplyTo')->willReturnSelf();
        $transportBuilder->method('getTransport')->willReturn($transport);

        $orderConfig = $this->createStub(OrderConfig::class);
        $orderConfig->method('getStateStatuses')->willReturnCallback(
            static function ($state): array {
                return $state === Order::STATE_NEW ? ['processing'] : [];
            }
        );

        $paymentInfo = $this->createStub(Payment::class);
        $paymentInfo->method('getOrder')->willReturn($order);

        $double = new PaylinkTestDouble(
            $this->createStub(OrderRepository::class),
            $this->createStub(Config::class),
            $scopeConfig,
            $locale,
            $paymentData,
            $addressRenderer,
            $transportBuilder,
            $this->createStub(PayHelper::class),
            $orderConfig
        );
        $double->paymentInfo = $paymentInfo;
        $double->payOrder = new class {
            /**
             * @return string
             */
            public function getOrderId(): string
            {
                return '123';
            }

            /**
             * @return string
             */
            public function getPaymentUrl(): string
            {
                return 'https://pay.nl/paylink';
            }
        };

        $stateObject = new DataObject();

        $double->initialize('order', $stateObject);

        $this->assertCount(2, $statusComments);
        $this->assertSame('Pay.: customer e-mail is empty, cannot send e-mail', $statusComments[0]);
        $this->assertStringContainsString('https://pay.nl/paylink', $statusComments[1]);
        $this->assertSame(Order::STATE_NEW, $stateObject->getState());
        $this->assertSame('processing', $stateObject->getStatus());
        $this->assertFalse($stateObject->getIsNotified());
    }
}
