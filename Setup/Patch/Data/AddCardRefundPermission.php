<?php
namespace Paynl\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;

class AddCardRefundPermission implements DataPatchInterface
{
    private $moduleDataSetup;
    private $roleFactory;
    private $rulesFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        RoleFactory $roleFactory,
        RulesFactory $rulesFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->roleFactory = $roleFactory;
        $this->rulesFactory = $rulesFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $roleCollection = $this->roleFactory->create()->getCollection()->addFieldToFilter('role_name', 'Administrators');

        foreach ($roleCollection as $role) {
            $this->rulesFactory->create()
                ->setData([
                    'role_id' => $role->getId(),
                    'resource_id' => 'Paynl_Payment::cardrefund',
                    'privileges' => null,
                    'permission' => 'allow'
                ])
                ->save();
        }

        $this->moduleDataSetup->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}