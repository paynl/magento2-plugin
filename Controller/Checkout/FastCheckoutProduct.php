<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Payment\Helper\PayHelper;

class FastCheckoutProduct extends \Magento\Checkout\Controller\Cart\Add
{
    /**
     * @var ResolverInterface
     */
    private $resolverInterface;

    /**
     * @var PayHelper;
     */
    private $payHelper;

    /**
     * @param Context $context
     * @param ResolverInterface $resolverInterface
     * @param PayHelper $payHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param RequestQuantityProcessor $quantityProcessor
     */
    public function __construct(
        Context $context,
        ResolverInterface $resolverInterface,
        PayHelper $payHelper,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        ?RequestQuantityProcessor $quantityProcessor = null
    ) {
        $this->resolverInterface = $resolverInterface;
        $this->payHelper = $payHelper;

        return parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );
    }

    /**
     * Add product to shopping cart action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            if (!$this->_formKeyValidator->validate($this->getRequest())) {
                throw new \Exception("Session has expired");
            }

            $params = $this->getRequest()->getParams();

            if (isset($params['qty'])) {
                $filter = new LocalizedToNormalized(
                    ['locale' => $this->resolverInterface->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            if (!$product) {
                throw new \Exception("Product not found");
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
            $this->cart->save();

            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            $baseUrl = $this->_url->getBaseUrl();

            return $this->goBack($baseUrl . 'paynl/checkout/fastcheckoutstart/');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->payHelper->logCritical('FC Product ERROR: ' . $e->getMessage(), []);
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage($e->getMessage());
            }

            return $this->goBack();
        } catch (\Exception $e) {
            $this->payHelper->logCritical('FC Product ERROR: ' . $e->getMessage(), []);
            $this->messageManager->addExceptionMessage($e, __('We can\'t add this item to your shopping cart right now.'));

            return $this->goBack();
        }
    }
}
