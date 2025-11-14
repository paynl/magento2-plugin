<?php

namespace Paynl\Payment\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Payment\Helper\PayHelper;
use PayNL\Sdk\Model\Pay\PayLoad;

class CreateFastCheckoutOrder
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var QuoteFactory
     */
    protected $quote;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ShippingMethodManagementInterface
     */
    private $shippingMethodManagementInterface;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepositoryInterface;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Paynl\Payment\Helper\PayHelper
     */
    private $payHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quote
     * @param QuoteManagement $quoteManagement
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderFactory $orderFactory
     * @param ShippingMethodManagementInterface $shippingMethodManagementInterface
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PayHelper $payHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        QuoteFactory $quote,
        QuoteManagement $quoteManagement,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderFactory $orderFactory,
        ShippingMethodManagementInterface $shippingMethodManagementInterface,
        OrderRepositoryInterface $orderRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PayHelper $payHelper
    ) {
        $this->storeManager = $storeManager;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderFactory = $orderFactory;
        $this->shippingMethodManagementInterface = $shippingMethodManagementInterface;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->payHelper = $payHelper;
    }

    /**
     * @param array $params
     * @return Order
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     * @throws \Exception
     */
    public function create(PayLoad $payLoad)
    {
        $checkoutData = $payLoad->getCheckoutData();

        $customerData = $checkoutData['customer'] ?? null;
        $billingAddressData = $checkoutData['billingAddress'] ?? null;
        $shippingAddressData = $checkoutData['shippingAddress'] ?? null;

        if (empty($customerData) || empty($billingAddressData) || empty($shippingAddressData)) {
            $this->payHelper->logCritical("Fast checkout: Missing data, cannot create order.", ['customerData' => $customerData, 'billingAddressData' => $billingAddressData, 'shippingAddressData' => $shippingAddressData]); // phpcs:ignore
            throw new \Exception("Missing data, cannot create order.");
        }

        $payOrderId = $payLoad->getPayOrderId();
        $quoteId = $payLoad->getReference();

        $this->payHelper->logDebug(__METHOD__ .': Creating fast checkout order', ['quoteId' => $quoteId]);

        try {
            $quote = $this->quote->create()->loadByIdWithoutStore($quoteId);
            if ($quote->getPayment()->getAdditionalInformation('payOrderId') != $payOrderId) {
                throw new \Exception("Payment ID mismatch");
            }

            $this->payHelper->logDebug('Fast checkout: quote->getId()', ['quoteId' => $quoteId, 'magentoQuoteId' => $quote->getId() ?? 'null']);

            if (empty($quote['is_active'])) {
                $this->payHelper->logDebug('Fast checkout: Quote inactive', ['quoteId' => $quoteId]);

                $existingOrder = $this->getExistingOrder($quoteId);
                if (empty($existingOrder)) {
                    $this->payHelper->logDebug('Fast checkout: Reactivating quote', ['quoteId' => $quoteId]);
                    $quote->setIsActive(true);
                    $quote->save();
                } else {
                    return $existingOrder;
                }
            }

            $storeId = $quote->getStoreId();

            $shippingMethodQuote = $quote->getShippingAddress()->getShippingMethod();

            $store = $this->storeManager->getStore($storeId);
            $websiteId = $store->getWebsiteId();

            $email = $customerData['email'];
            $customer = $this->customerFactory->create()
                ->setWebsiteId($websiteId)
                ->loadByEmail($email);

            if (!$customer->getEntityId()) {
                $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($customerData['firstName'])
                    ->setLastname($customerData['lastName'])
                    ->setEmail($email)
                    ->setPassword($email);
                $customer->save();
            }

            $customer = $this->customerRepository->getById($customer->getEntityId());

            $quote->assignCustomer($customer);
            $quote->setSendConfirmation(1);

            $quote->getBillingAddress()->addData(array(
                'customer_address_id' => '',
                'prefix' => '',
                'firstname' => $billingAddressData['firstName'] ?? $customerData['firstName'],
                'middlename' => '',
                'lastname' => $billingAddressData['lastName'] ?? $customerData['lastName'],
                'suffix' => '',
                'company' => $customerData['company'] ?? '',
                'street' => array(
                    '0' => $billingAddressData['streetName'],
                    '1' => $billingAddressData['streetNumber'] . ($billingAddressData['streetNumberAddition'] ?? ''),
                ),
                'city' => $billingAddressData['city'],
                'country_id' => $billingAddressData['countryCode'],
                'region' => $billingAddressData['regionCode'] ?? '',
                'postcode' => $billingAddressData['zipCode'],
                'telephone' => $customerData['phone'],
                'fax' => '',
                'vat_id' => '',
                'save_in_address_book' => 1,
            ));

            $quote->getShippingAddress()->addData(array(
                'customer_address_id' => '',
                'prefix' => '',
                'firstname' => $shippingAddressData['firstName'] ?? $customerData['firstName'],
                'middlename' => '',
                'lastname' => $shippingAddressData['lastName'] ?? $customerData['lastName'],
                'suffix' => '',
                'company' => $customerData['company'] ?? '',
                'street' => array(
                    '0' => $shippingAddressData['streetName'],
                    '1' => $shippingAddressData['streetNumber'] . ($shippingAddressData['streetNumberAddition'] ?? ''),
                ),
                'city' => $shippingAddressData['city'],
                'country_id' => $shippingAddressData['countryCode'],
                'region' => $shippingAddressData['regionCode'] ?? '',
                'postcode' => $shippingAddressData['zipCode'],
                'telephone' => $customerData['phone'],
                'fax' => '',
                'vat_id' => '',
                'save_in_address_book' => 1,
            ));

            $shippingAddress = $quote->getShippingAddress();

            if (!$quote->getIsVirtual()) {
                $shippingAddress->setCollectShippingRates(true)->collectShippingRates();

                $shippingData = $this->shippingMethodManagementInterface->getList($quote->getId());
                $shippingMethodsAvailable = [];
                foreach ($shippingData as $shipping) {
                    $code = $shipping->getCarrierCode() . '_' . $shipping->getMethodCode();
                    $shippingMethodsAvailable[$code] = $code;
                }

                $this->payHelper->logDebug('Fast checkout: Available shipping methods', $shippingMethodsAvailable);

                if (!empty($shippingMethodsAvailable[$shippingMethodQuote])) {
                    $shippingMethod = $shippingMethodQuote;
                } elseif (!empty($shippingMethodsAvailable[$store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping')])) {
                    $shippingMethod = $store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping');
                } elseif (!empty($shippingMethodsAvailable[$store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping_backup')])) {
                    $shippingMethod = $store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping_backup');
                }

                $this->payHelper->logDebug('Fast checkout: Shipping options', ['quote' => $shippingMethodQuote, 'setting' => $store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping'), 'setting_backup' => $store->getConfig('payment/paynl_payment_ideal/fast_checkout_shipping_backup')]); // phpcs:ignore

                if (empty($shippingMethod)) {
                    throw new \Exception("No shipping method available");
                }

                $shippingAddress->setShippingMethod($shippingMethod);
            } else {
                $this->payHelper->logDebug('Fast checkout: Virtual quote detected, skipping shipping method');
            }

            $quote->setPaymentMethod('paynl_payment_ideal');
            $quote->setInventoryProcessed(false);
            $quote->save();

            # Set Sales Order Payment
            $quote->getPayment()->importData(['method' => 'paynl_payment_ideal']);
            $quote->collectTotals()->save();

            $service = $this->quoteManagement->submit($quote);
            $increment_id = $service->getRealOrderId();
            $this->payHelper->logDebug('Fast checkout: Reserved increment_id', [$increment_id]);

            $order = $this->orderFactory->create()->loadByIncrementId($increment_id);
            $additionalData = $order->getPayment()->getAdditionalInformation();
            $additionalData['transactionId'] = $payOrderId;
            $order->getPayment()->setAdditionalInformation($additionalData);
            $order->save();
            $this->payHelper->logDebug('Fast checkout: Created order_id', [$order->getId()]);

            $order->addStatusHistoryComment(__('PAY. - Created iDEAL Fast Checkout order'))->save();
        } catch (NoSuchEntityException $e) {
            $this->payHelper->logDebug('Fast checkout: Quote not found', ['quoteId' => $quoteId, 'error' => $e->getMessage()]);
            $order = $this->getExistingOrder($quoteId);
        } catch (\Exception $e) {
            $this->payHelper->logDebug('Fast checkout: Exception on create', ['quoteId' => $quoteId, 'error' => $e->getMessage()]);
            throw new \Exception("Exception on create. " . $e->getMessage());
        }
        if (empty($order)) {
            $this->payHelper->logCritical('Fast checkout: Both order & quote not found', ['quoteId' => $quoteId, 'searchCriteria' => $searchCriteria, 'searchResult' => $searchResult]);
            throw new \Exception("Order & Quote can't be found. " . $quoteId, 404);
        }
        return $order;
    }

    /**
     * @param string $quoteId
     * @return Order|boolean
     * @throws \Exception
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function getExistingOrder($quoteId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('quote_id', $quoteId)->create();
        $searchResult = $this->orderRepositoryInterface->getList($searchCriteria)->getItems();
        if (is_array($searchResult) && !empty($searchResult)) {
            $order = array_shift($searchResult);
        }
        if (empty($order)) {
            return false;
        }
        return $order;
    }
}
