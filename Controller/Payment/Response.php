<?php

namespace Laybuy\Laybuy\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Laybuy\Laybuy\Model\Config as LaybuyConfig;
use Laybuy\Laybuy\Model\Laybuy;
use Laybuy\Laybuy\Model\LaybuyFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Laybuy\Laybuy\Model\Logger\Logger;

class Response extends Action
{

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var \Laybuy\Laybuy\Model\Laybuy
     */
    protected $laybuy;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * Response constructor.
     * @param Logger $logger
     * @param LaybuyFactory $laybuyFactory
     * @param CheckoutSession $checkoutSession
     * @param CartManagementInterface $cartManagement
     * @param Context $context
     */
    public function __construct(
        Logger $logger,
        LaybuyFactory $laybuyFactory,
        CheckoutSession $checkoutSession,
        CartManagementInterface $cartManagement,
        Context $context
    ) {
        $this->logger = $logger;
        $this->laybuy = $laybuyFactory->create();
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->logger->debug([__METHOD__ => 'start']);

        $token = $this->getRequest()->getParam('token');
        $laybuyStatus = $this->getRequest()->getParam('status');

        try {
            if ($this->laybuy->getConfigPaymentAction() == Laybuy::ACTION_AUTHORIZE_CAPTURE) {
                if ($laybuyStatus == LaybuyConfig::LAYBUY_SUCCESS) {
                    if ($laybuyOrderId = $this->laybuy->laybuyConfirm($token)) {
                        $quote = $this->checkoutSession->getQuote();
                        $quote->getPayment()->setAdditionalInformation('Reference Order Id', $laybuyOrderId);
                        $quote->getPayment()->setAdditionalInformation('Token', $token);

                        $this->checkoutSession
                            ->setLastQuoteId($quote->getId())
                            ->setLastSuccessQuoteId($quote->getId())
                            ->clearHelperData();

                        $quote->collectTotals();

                        $orderId = $this->cartManagement->placeOrder($quote->getId());

                        $order = $this->checkoutSession->getLastRealOrder();

                        if ($orderId && $order) {

                            $this->laybuy->sendOrderEmail($order);

                            $this->logger->debug([
                                'Order created' => $order->getId(),
                                'Laybuy order' => $laybuyOrderId
                            ]);

                            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                        }
                    } else {
                        $message = 'Laybuy: There was an error, payment failed.';
                        $this->messageManager->addErrorMessage($message);

                        return $this->_redirect('checkout/cart', ['_secure' => true]);
                    }
                } else {
                    if ($laybuyStatus == LaybuyConfig::LAYBUY_CANCELLED) {
                        $message = 'Laybuy payment was Cancelled.';
                        $this->messageManager->addErrorMessage('Laybuy payment was Cancelled.');
                    } else {
                        $message = 'Laybuy: There was an error, payment failed.';
                        $this->messageManager->addErrorMessage($message);
                    }
                    $this->laybuy->laybuyCancel($token);

                    return $this->_redirect('checkout/cart', ['_secure' => true]);
                }


            } else {
                $order = $this->checkoutSession->getLastRealOrder();

                $laybuyStatus = strtoupper($this->getRequest()->getParam('status'));
                $token = $this->getRequest()->getParam('token');
                if ($laybuyStatus == LaybuyConfig::LAYBUY_SUCCESS) {
                    $laybuyOrderId = $this->laybuy->laybuyConfirm($token);

                    $this->logger->debug(['Order created' => $order->getId(), 'Laybuy order' => $laybuyOrderId]);

                    if ($laybuyOrderId) {
                        $txnId = $laybuyOrderId . '_' . $token;
                        if ($this->laybuy->processLaybuySuccessPayment($order, $token, $txnId, $laybuyOrderId)) {
                            $this->logger->debug([
                                'Order Successfully Updated' => $order->getId(),
                                'Laybuy order' => $laybuyOrderId
                            ]);
                            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                        } else {
                            $this->logger->debug([
                                'Order Update Error' => $order->getId(),
                                'Laybuy order' => $laybuyOrderId
                            ]);
                            $this->messageManager->addErrorMessage('Laybuy order confirmation error.');
                            throw new \Exception();
                        }
                    }

                } else {
                    if ($laybuyStatus == LaybuyConfig::LAYBUY_CANCELLED) {
                        $message = 'Laybuy payment was Cancelled.';
                        $this->messageManager->addErrorMessage('Laybuy payment was Cancelled.');
                    } else {
                        $message = 'Laybuy: There was an error, payment failed.';
                        $this->messageManager->addErrorMessage($message);
                    }
                    $this->laybuy->cancelMagentoOrder($order, $token, $message);

                    if ($order->canCancel()) {
                        $order->cancel();
                    }

                    return $this->_redirect('checkout/cart', ['_secure' => true]);

                }

            }


        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Laybuy: There was an error confirming your order');
            $this->logger->debug(['process error ' => $e->getTraceAsString()]);
            $this->messageManager->addExceptionMessage($e, $e->getMessage());

            if (!$order->getId()) {
                $this->laybuy->laybuyCancel($token);
            }

            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}