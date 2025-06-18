<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\ResourceConnection;

class MultiCoreOptions implements ArrayInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ScopeConfigInterface $scopeConfig, ResourceConnection $resourceConnection)
    {
        $this->scopeConfig = $scopeConfig;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arrOptions = $this->toArray();

        $arrResult = [];
        foreach ($arrOptions as $value => $label) {
            $arrResult[] = ['value' => $value, 'label' => $label];
        }
        return $arrResult;
    }

    /**
     * @param string $scope
     * @param int $scopeId
     * @return string|null
     */
    private function getCoresFromDb(string $scope = 'default', int $scopeId = 0): ?string
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from('core_config_data', ['value'])
            ->where('path = ?', 'payment/paynl/cores')
            ->where('scope = ?', $scope)
            ->where('scope_id = ?', $scopeId)
            ->limit(1);

        return $connection->fetchOne($select);
    }


    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        # DB Retrieval to prevent cache issues
        $payCores = $this->getCoresFromDb($scopeType, 0);
        $cores = [];

        if (!empty($payCores)) {
            $payCores = json_decode($payCores, true);
        }
        if (is_array($payCores)) {
            foreach ($payCores as $core) {
                $cores[$core['domain']] = $core['label'] ?? $core['domain'];
            }
        }
        $cores['custom'] = __('Custom');

        return $cores;
    }

}
