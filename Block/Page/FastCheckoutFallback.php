<?php

namespace Paynl\Payment\Block\Page;

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Page\Config;

class FastCheckoutFallback extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var CacheInterface
     */
    public $cache;

    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @param Context $context
     * @param Request $request
     * @param Response $response
     * @param Cart $cart
     * @param CacheInterface $cache
     * @param Config $page
     * @param ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Request $request,
        Response $response,
        Cart $cart,
        CacheInterface $cache,
        Config $page,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        $page->addPageAsset('Paynl_Payment::css/payFastCheckout.css');
        parent::__construct($context, $data);
        $this->cart = $cart;
        $this->request = $request;
        $this->response = $response;
        $this->cache = $cache;
        $this->messageManager = $messageManager;
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml() // phpcs:ignore
    {
        return parent::_beforeToHtml();
    }

    /**
     * @return array
     */
    public function getShippingMethods()
    {
        $cacheName = 'shipping_methods_' . $this->cart->getQuote()->getId();
        $shippingMethodJson = $this->cache->load($cacheName);

        if (empty($shippingMethodJson)) {
            $this->messageManager->addNoticeMessage(__('Unfortunately fast checkout is currently not possible.'));
            $this->response->setRedirect('/checkout/cart');
        } else {
            return json_decode($shippingMethodJson);
        }
    }

    /**
     * @param string $param
     * @return string|null
     */
    public function getParam($param)
    {
        $params = $this->request->getParams();
        return (isset($params[$param])) ? $params[$param] : null;
    }
}
