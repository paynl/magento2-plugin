<?php

namespace Paynl\Payment\Controller\Csp;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Paynl\Payment\Helper\PayHelper;

class Report extends Action implements CsrfAwareActionInterface
{
    private const MAX_LOG_BYTES = 8192;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var PayHelper
     */
    private $payHelper;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param PayHelper $payHelper
     */
    public function __construct(
        Context    $context,
        RawFactory $resultRawFactory,
        PayHelper  $payHelper
    )
    {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->payHelper = $payHelper;
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
     * @param RequestInterface $request
     * @return bool
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

    /**
     * @return Raw
     */
    public function execute(): Raw
    {
        $content = (string)$this->getRequest()->getContent();
        if ($content !== '') {
            $this->payHelper->logDebug('CSP report received', $this->summarizeReport($content));
        }

        $result = $this->resultRawFactory->create();
        $result->setHttpResponseCode(204);
        $result->setContents('');

        return $result;
    }

    /**
     * @param string $content
     * @return array|array[]|string[]
     */
    private function summarizeReport(string $content): array
    {
        $truncated = strlen($content) > self::MAX_LOG_BYTES
            ? substr($content, 0, self::MAX_LOG_BYTES) . '...'
            : $content;

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return ['raw' => $truncated];
        }

        if (isset($decoded['csp-report']) && is_array($decoded['csp-report'])) {
            return $this->extractClassicReport($decoded['csp-report'], $truncated);
        }

        if (array_is_list($decoded)) {
            $summaries = [];
            foreach ($decoded as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $body = $item['body'] ?? null;
                if (is_array($body)) {
                    $summaries[] = $this->extractReportingApiBody($body);
                }
            }
            if ($summaries !== []) {
                return ['reports' => $summaries];
            }
        }

        return ['raw' => $truncated];
    }

    /**
     * @param array $report
     * @param string $truncated
     * @return array
     */
    private function extractClassicReport(array $report, string $truncated): array
    {
        return [
            'document-uri' => $report['document-uri'] ?? null,
            'violated-directive' => $report['violated-directive'] ?? null,
            'effective-directive' => $report['effective-directive'] ?? null,
            'blocked-uri' => $report['blocked-uri'] ?? null,
            'source-file' => $report['source-file'] ?? null,
            'raw' => $truncated,
        ];
    }

    /**
     * @param array $body
     * @return array
     */
    private function extractReportingApiBody(array $body): array
    {
        return [
            'documentURL' => $body['documentURL'] ?? $body['document-uri'] ?? null,
            'effectiveDirective' => $body['effectiveDirective'] ?? $body['violated-directive'] ?? null,
            'blockedURL' => $body['blockedURL'] ?? $body['blocked-uri'] ?? null,
            'sourceFile' => $body['sourceFile'] ?? $body['source-file'] ?? null,
        ];
    }
}
