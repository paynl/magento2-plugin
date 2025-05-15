<?php

namespace Paynl\Payment\Model;

class PayOrder
{
    /** @var array */
    private $data;

    public function __construct(array $payOrderData)
    {
        $this->data = $payOrderData;
    }

    public function getExtra3(): ?string
    {
        return isset($this->data['stats']['extra3']) ? $this->data['stats']['extra3'] : null;
    }

    public function isPending(): bool
    {
        return isset($this->data['status']['code']) && (int)$this->data['status']['code'] === 20;
    }

    public function isPaid(): bool
    {
        return isset($this->data['status']['code']) && (int)$this->data['status']['code'] === 100;
    }

    public function isAuthorized(): bool
    {
        return isset($this->data['status']['code']) && (int)$this->data['status']['code'] === 95;
    }

    public function isCanceled(): bool
    {
        return false;
    }

    public function isRefunded(bool $full = true): bool
    {
        return false;
    }

    public function isChargeBack(): bool
    {
        return false;
    }

    public function isPartialPayment(): bool
    {
        return false;
    }

    public function getReference(): ?string
    {
        return isset($this->data['reference']) ? $this->data['reference'] : null;
    }

    public function getId(): ?string
    {
        return isset($this->data['id']) ? $this->data['id'] : null;
    }

    public function getOrderId(): ?string
    {
        return isset($this->data['orderId']) ? $this->data['orderId'] : null;
    }

    public function getCheckoutData(): array
    {
        return isset($this->data['checkoutData']) && is_array($this->data['checkoutData'])
            ? $this->data['checkoutData']
            : [];
    }

    public function getCurrencyAmount(): ?float
    {
        return isset($this->data['paymentDetails']['amount']['currency'])
            ? (float)$this->data['paymentDetails']['amount']['currency']
            : null;
    }

    public function getPaidCurrencyAmount(): ?float
    {
        return isset($this->data['paymentDetails']['paidAmount']['currency'])
            ? (float)$this->data['paymentDetails']['paidAmount']['currency']
            : null;
    }

    public function getPaidAmount(): ?float
    {
        return isset($this->data['amount']['value'])
            ? (float)$this->data['amount']['value'] / 100
            : null;
    }
}
