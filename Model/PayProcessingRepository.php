<?php

namespace Paynl\Payment\Model;

class PayProcessingRepository
{
    private $resourceConnection;

    const TABLE = 'pay_processing';

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(\Magento\Framework\App\ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * @return string
     */
    private function getTable()
    {
        return $this->resourceConnection->getTableName(self::TABLE);
    }

    /**
     * Inserts a new entry into the database table with the specified pay order ID and type.
     *
     * @param mixed $payOrderId The identifier for the pay order.
     * @param mixed $type The type associated with the entry.
     * @return void
     */
    public function createEntry($payOrderId, $type)
    {
        $this->getConnection()->insert($this->getTable(), ['payOrderId' => $payOrderId, 'type' => $type]);
    }

    /**
     * @param $payOrderId
     * @param $type
     * @param $timeInterval
     * @return bool
     */
    public function existsEntry($payOrderId, $type, $timeInterval = 1)
    {
        $select = $this->getConnection()->select()
            ->from($this->getTable())
            ->where('payOrderId = ?', $payOrderId)
            ->where('type = ?', $type)
            ->where('created_at > date_sub(now(), interval ' . (int)$timeInterval . ' minute)');

        return (bool)$this->getConnection()->fetchOne($select);
    }

    /**
     * Retrieves a list of entries filtered by type.
     *
     * @param string $type The type of entries to filter by.
     * @return array An array of payOrderId values for the specified type.
     */
    public function getEntriesByType($type)
    {
        $select = $this->getConnection()->select()
            ->from($this->getTable(), ['payOrderId'])
            ->where('type = ?', $type);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Deletes an entry from the database based on the specified payOrderId and type.
     *
     * @param mixed $payOrderId The ID of the pay order to delete.
     * @param mixed $type The type associated with the entry to delete.
     * @return $this
     */
    public function deleteEntry($payOrderId, $type): self
    {
        $this->getConnection()->delete($this->getTable(), ['payOrderId = ?' => $payOrderId, 'type = ?' => $type]);
        return $this;
    }

}
