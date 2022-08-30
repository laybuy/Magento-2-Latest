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
use \Magento\Checkout\Model\Cart;

class Response extends Action
{

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

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
    protected $orderRepository;
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
        Laybuy $laybuy,
        CheckoutSession $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        CartManagementInterface $cartManagement,
        Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->logger = $logger;
        $this->laybuy = $laybuy;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->logger->debug([__METHOD__ => 'start']);

        /**
         * Fix issue with session clear during order email sending.
         * @see \Magento\PageCache\Model\DepersonalizeChecker::checkIfDepersonalize
         */
        $this->getRequest()->setParams(array_merge(
            $this->getRequest()->getParams(),
            ['ajax' => 1]
        ));


        $token = $this->getRequest()->getParam('token');
        $laybuyStatus = $this->getRequest()->getParam('status');
        $quoteId = $this->getRequest()->getParam('qID');
        $fromPWA = false;
        $quote = $this->checkoutSession->getQuote();
        if (!empty($quoteId)) {
            $quote = $this->quoteRepository->get($quoteId);
            $fromPWA = true;
        }

        $merchantReference = $quote->getReservedOrderId();

        try {
            if ($this->laybuy->getConfigPaymentAction() == Laybuy::ACTION_AUTHORIZE_CAPTURE) {
                if ($laybuyStatus == LaybuyConfig::LAYBUY_SUCCESS && $quote &&
                    $quote->getPayment()->getAdditionalInformation('Token') == $token) {
                    if ($quote->getPayment()->getAdditionalInformation('laybuy_grand_total') == $quote->getGrandTotal() && $laybuyOrderId = $this->laybuy->laybuyConfirm($token)) {
                        $quote->getPayment()->setAdditionalInformation(LaybuyConfig::LAYBUY_FIELD_REFERENCE_ORDER_ID, $laybuyOrderId);
                        if(!$this->laybuy->getConfigData('store_token_data')) {
                            $quote->getPayment()->unsAdditionalInformation('Token', $token);
                        }

                            $this->checkoutSession
                            ->setLastQuoteId($quote->getId())
                            ->setLastSuccessQuoteId($quote->getId())
                            ->clearHelperData();


                        $quote->collectTotals();
                        $this->logger->debug([
                            'Quote ID:' => $quote->getId(),
                            'Token:' => $token,
                            'Laybuy Order ID:' => $laybuyOrderId,
                            'Payment ID:' => $quote->getPayment()->getId()
                        ]);

                        $orderId = $this->cartManagement->placeOrder($quote->getId());

                        $order = $this->checkoutSession->getLastRealOrder();

                        if($fromPWA) {
                            $order = $this->orderRepository->get($orderId);
                        }

                        if ($orderId && $order) {

                            $txnId = $laybuyOrderId . '_' . $token;
                            $this->laybuy->addTransactionId($order,$txnId);
                            $this->laybuy->sendOrderEmail($order);

                            $this->logger->debug([
                                'Order created' => $order->getId(),
                                'Laybuy order' => $laybuyOrderId
                            ]);

                            if ($fromPWA) {
                                return $this->_redirect('checkout', ['_secure' => true]);
                            }

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
                    if ($fromPWA) {
                        return $this->_redirect('cart', ['_secure' => true]);
                    } else {
                        return $this->_redirect('checkout/cart', ['_secure' => true]);
                    }
                }


            } else {

                $order = $this->checkoutSession->getLastRealOrder();

                if($fromPWA) {
                    $orderId = $this->cartManagement->placeOrder($quote->getId());
                    $order = $this->orderRepository->get($orderId);
                }

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

                            if($fromPWA) {
                                return $this->_redirect('checkout/', ['_secure' => true]);
                            }

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
                    if ($fromPWA) {
                        return $this->_redirect('cart', ['_secure' => true]);
                    }
                    return $this->_redirect('checkout/cart', ['_secure' => true]);

                }

            }


        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Laybuy: There was an error confirming your order');
            $this->logger->debug(['process error ' => $e->getTraceAsString()]);
            $this->messageManager->addExceptionMessage($e, $e->getMessage());

            if (!isset($orderId) || isset($order) && !$order->getId()) {
                $this->laybuy->laybuyCancel($token);
            }

            if($laybuyOrder = $this->laybuy->laybuyCheckOrder($merchantReference)) {
                $this->laybuy->refundLaybuy($laybuyOrder->orderId, $laybuyOrder->amount, $quote->getStoreId());
            }
            if ($fromPWA) {
                return $this->_redirect('cart', ['_secure' => true]);
            }
            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }
        if ($fromPWA) {
            return $this->_redirect('cart', ['_secure' => true]);
        }
        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}
