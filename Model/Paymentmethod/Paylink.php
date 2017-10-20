<?php
namespace Paynl\Payment\Model\Paymentmethod;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

/**
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Paylink extends PaymentMethod {
	protected $_code = 'paynl_payment_paylink';

	/**
	 * Paylink payment block paths
	 *
	 * @var string
	 */
	protected $_formBlockType = \Paynl\Payment\Block\Form\Paylink::class;

	/**
	 * @var OrderRepository
	 */
    protected $_orderRepository;
    // this is an admin only method
    protected $_canUseCheckout = false;

	function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
		\Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
		\Magento\Payment\Helper\Data $paymentData,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Payment\Model\Method\Logger $logger,
		OrderRepository $orderRepository,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = [] ) {
		parent::__construct( $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data );
		$this->_orderRepository = $orderRepository;
	}

	public function initialize( $paymentAction, $stateObject ) {
		if($paymentAction == 'order') {
			/** @var Order $order */
			$order       = $this->getInfoInstance()->getOrder();
			$this->_orderRepository->save($order);
			$transaction = $this->doStartTransaction( $order );
			$state = $this->getConfigData('order_status');
			parent::initialize( $paymentAction, $stateObject );
			$order->addStatusHistoryComment('Betaallink: '.$transaction->getRedirectUrl(), $state);
		}
	}
	public function assignData( \Magento\Framework\DataObject $data ) {
		$this->getInfoInstance()->setAdditionalInformation('valid_days', $data->getData('additional_data')['valid_days']);
		return parent::assignData( $data );
	}
}