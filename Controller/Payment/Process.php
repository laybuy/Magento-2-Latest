<?php

namespace Laybuy\Laybuy\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Laybuy\Laybuy\Model\Logger\Logger;
use Laybuy\Laybuy\Model\LaybuyFactory;
use \Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;

/**
 * Class Process
 * @package Laybuy\Laybuy\Controller\Payment
 */
class Process extends Action
{
    protected $logger;

    /**
     * @var \Laybuy\Laybuy\Model\Laybuy
     */
    protected $laybuy;

    /**
     * @var JsonResultFactory
     */
    protected $jsonFactory;

    /**
     * Process constructor.
     * @param Logger $logger
     * @param LaybuyFactory $laybuyFactory
     * @param JsonResultFactory $jsonFactory
     * @param Context $context
     */
    public function __construct(
        Logger $logger,
        LaybuyFactory $laybuyFactory,
        JsonResultFactory $jsonFactory,
        Context $context
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->laybuy = $laybuyFactory->create();
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->logger->debug([__METHOD__ => 'start']);

        $guest_email = $this->getRequest()->getParam('guest-email');

        $redirectUrl = $this->laybuy->getLaybuyRedirectUrl($guest_email);

        if ($redirectUrl) {
            $this->logger->debug([__METHOD__ . '  LAYBUY REDIRECT URL ' => $redirectUrl]);

            return $this->jsonFactory->create()->setData(['success' => true, 'redirect_url' => $redirectUrl]);
        }

        $this->logger->debug([__METHOD__ . '  LAYBUY STATUS ' => 'FAILED']);
        $this->messageManager->addErrorMessage(__('Couldn\'t initialize Laybuy payment method.'));

        return $this->jsonFactory->create()->setData(['success' => false]);
    }
}