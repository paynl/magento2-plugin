<?php

/**
 * Controller class responsible for handling the capture process of payment orders.
 * Implements CsrfAwareActionInterface to handle CSRF validation and exception generation.
 */

namespace Paynl\Payment\Controller\Process;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\ResourceConnection;
use PayNL\Sdk\Model\Request\OrderCaptureRequest;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Model\PayProcessingRepository;
use Magento\Framework\Controller\Result\Json;
use Throwable;
use Paynl\Payment\Helper\PayHelper;

class Capture extends Action implements CsrfAwareActionInterface
{
    private $payProcessingRepository;
    private $config;
    private $resultJsonFactory;
    private $resourceConnection;

    /**
     * @var \Paynl\Payment\Helper\PayHelper
     */
    private $payHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param PayProcessingRepository $payProcessingRepository
     * @param ResourceConnection $resourceConnection
     * @param PayHelper $payHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Config $config,
        PayProcessingRepository $payProcessingRepository,
        ResourceConnection $resourceConnection,
        PayHelper $payHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->payProcessingRepository = $payProcessingRepository;
        $this->resourceConnection = $resourceConnection;
        $this->payHelper = $payHelper;
    }

    /**
     * @param string $scope
     * @param int $scopeId
     * @return string|null
     */
    private function getProcessSecretFromDb(string $scope = 'default', int $scopeId = 0): ?string
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName('core_config_data'), ['value'])
            ->where('path = ?', 'payment/paynl/process_secret')
            ->where('scope = ?', $scope)
            ->where('scope_id = ?', $scopeId)
            ->limit(1);

        return $connection->fetchOne($select);
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $request = $this->getRequest();
        $payOrderId = $request->getParam('payOrderId');
        $token = $request->getParam('token');
        $message = '';

        $this->payHelper->logDebug('Capture controller ' . $payOrderId);

        $secret = $this->getProcessSecretFromDb();
        $expectedToken = hash('sha256', $secret . $payOrderId);

        if (empty($expectedToken) || $token !== $expectedToken) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Forbidden: invalid token'
            ], 403);
        }

        $this->payProcessingRepository->createEntry($payOrderId, 'queue_capture');

        try {
            $orderCaptureRequest = new OrderCaptureRequest($payOrderId);
            $orderCaptureRequest->setConfig($this->config->getPayConfig());
            $orderCaptureRequest->start();
            $result = true;

        } catch (Throwable $e) {
            $message = $e->getMessage();
            $this->payHelper->logDebug('Error while processing capture ' . $e->getMessage(), [$payOrderId]);
            $result = false;
        }

        return $this->jsonResponse([
            'success' => $result,
            'message' => (($result === true) ? 'Success' : $message)
        ]);
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param array $data
     * @param int $httpCode
     * @return Json
     */
    private function jsonResponse(array $data, int $httpCode = 200): Json
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode($httpCode);
        $result->setData($data);
        return $result;
    }
}
