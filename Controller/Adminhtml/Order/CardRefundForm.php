<?php
namespace Paynl\Payment\Controller\Adminhtml\Order;

use Magento\Framework\Controller\ResultFactory;

class CardRefundForm extends \Magento\Backend\App\Action
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        return $resultPage;
    }
}
