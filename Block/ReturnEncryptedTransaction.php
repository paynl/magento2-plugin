<?php
namespace Paynl\Payment\Block;

use Magento\Framework\View\Element\Template;

/**
 * Class ReturnEncryptedTransaction
 * @package Paynl\Payment\Block
 */
class ReturnEncryptedTransaction extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * ReturnEncryptedTransaction constructor.
     * @param Template\Context $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_request = $request;
        $this->_jsonHelper = $jsonHelper;
    }

    /**
     * Return event details, for the DOM CustomEvent to be launched. This is used on the iframe return url.
     *
     * @return string
     */
    public function getEventDetails()
    {
        return $this->_jsonHelper->jsonEncode(array(
            'detail' => $this->_request->getParams()
        ));
    }
}
