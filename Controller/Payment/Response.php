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
use Laybuy\Laybuy\Model\Email;

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
     * @var Email
     */
    protected $emailError;

    /**
     * Response constructor.
     * @param Logger $logger
     * @param LaybuyFactory $laybuyFactory
     * @param CheckoutSession $checkoutSession
     * @param CartManagementInterface $cartManagement
     * @param Email $emailError
     * @param Context $context
     */
    public function __construct(
        Logger $logger,
        LaybuyFactory $laybuyFactory,
        CheckoutSession $checkoutSession,
        CartManagementInterface $cartManagement,
        Email $emailError,
        Context $context
    ) {
        $this->logger = $logger;
        $this->laybuy = $laybuyFactory->create();
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        $this->emailError = $emailError;
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
        $quote = $this->checkoutSession->getQuote();
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

                        if ($orderId && $order) {

                            $txnId = $laybuyOrderId . '_' . $token;
                            $this->laybuy->addTransactionId($order,$txnId);
                            $this->laybuy->sendOrderEmail($order);

                            $this->logger->debug([
                                'Order created' => $order->getId(),
                                'Laybuy order' => $laybuyOrderId
                            ]);

                            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                        } else {
                            $this->messageManager->addErrorMessage('Order not exist on Magento');
                            $this->logger->debug(['Order can not create from quote' => $quote->getId()]);
                            $errorData = [
                                'error_message' => 'Order can not create from quote',
                                'addition_log' => 'quote_id: '.$quote->getId(),
                                'response' => json_encode($this->getRequest()->getParams())
                            ];
                            $this->emailError->sendCheckoutReportEmail($errorData, $order->getStoreId());
                        }
                    } else {
                        $message = 'Laybuy: There was an error, payment failed.';
                        $this->messageManager->addErrorMessage($message);
                        $errorData = [
                            'error_message' => $message,
                            'addition_log' => 'quote_id: '.$quote->getId(),
                            'response' => json_encode($this->getRequest()->getParams())
                        ];
                        $this->emailError->sendCheckoutReportEmail($errorData, $quote->getStoreId());
                        return $this->_redirect('checkout/cart', ['_secure' => true]);
                    }
                } else {
                    if ($laybuyStatus == LaybuyConfig::LAYBUY_CANCELLED) {
                        $message = 'Laybuy payment was Cancelled.';
                        $this->messageManager->addErrorMessage('Laybuy payment was Cancelled.');
                        $errorData = [
                            'error_message' => $message,
                            'addition_log' => 'quote_id: '.$quote->getId(),
                            'response' => json_encode($this->getRequest()->getParams())
                        ];
                    } else {
                        $message = 'Laybuy: There was an error, payment failed.';
                        $this->messageManager->addErrorMessage($message);
                        $errorData = [
                            'error_message' => $message,
                            'addition_log' => 'quote_id: '.$quote->getId(),
                            'response' => json_encode($this->getRequest()->getParams())
                        ];
                    }
                    $this->emailError->sendCheckoutReportEmail($errorData, $quote->getStoreId());
                    $this->laybuy->laybuyCancel($token);

                    return $this->_redirect('checkout/cart', ['_secure' => true]);
                }


            } else {
                $order = $this->checkoutSession->getLastRealOrder();

                if ($order->getId()) {
                    $paymentInformation = $order->getPayment()->getAdditionalInformation();
                    if (!(isset($paymentInformation['Token']) && $paymentInformation['Token'] == $token)) {
                        $message = 'Wrong Token';
                        $this->logger->debug([
                            'message' => $message,
                            'response_token' => $token,
                            'order_id' => $order->getId()
                        ]);
                        $errorData = [
                            'error_message' => $message,
                            'addition_log' => 'order_id: '.$order->getId(),
                            'response' => json_encode($this->getRequest()->getParams())
                        ];
                        $this->emailError->sendCheckoutReportEmail($errorData, $order->getStoreId());

                        $this->messageManager->addErrorMessage('Wrong Token');
                        return $this->_redirect('checkout/cart', ['_secure' => true]);
                    }
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
                            $this->laybuy->sendOrderEmail($order);
                            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                        } else {
                            $this->logger->debug([
                                'Order Update Error' => $order->getId(),
                                'Laybuy order' => $laybuyOrderId
                            ]);
                            $this->messageManager->addErrorMessage('Laybuy order confirmation error.');
                            $errorData = [
                                'error_message' => 'Laybuy order confirmation error.',
                                'addition_log' => 'laybuy_order: '.$laybuyOrderId. ' ,order_id: '.$order->getId(),
                                'response' => json_encode($this->getRequest()->getParams())
                            ];
                            $this->emailError->sendCheckoutReportEmail($errorData, $order->getStoreId());
                            throw new \Exception();
                        }
                    } else {
                        $message = 'Laybuy order ID did not exist.';
                        $this->messageManager->addErrorMessage($message);
                        $this->logger->debug([
                            $message,
                            'Laybuy status' => $laybuyStatus,
                            'order_id' => $order->getId()
                        ]);
                        $errorData = [
                            'error_message' => $message,
                            'addition_log' => 'laybuy_status: '.$laybuyStatus. ' ,order_id: '.$order->getId(),
                            'response' => json_encode($this->getRequest()->getParams())
                        ];
                        $this->emailError->sendCheckoutReportEmail($errorData, $order->getStoreId());
                    }

                } else {
                    if ($laybuyStatus == LaybuyConfig::LAYBUY_CANCELLED) {
                        $message = 'Laybuy payment was Cancelled.';
                        $this->messageManager->addErrorMessage('Laybuy payment was Cancelled.');
                        $errorData = [
                            'error_message' => $message,
                            'addition_log' => 'order_id: '. $order->getId(),
                            'response' => json_encode($this->getRequest()->getParams())
                        ];
                    } else {
                        $message = 'Laybuy: There was an error, payment failed.';
                        $this->messageManager->addErrorMessage($message);
                        $errorData = [
                            'error_message' => $message,
                            'addition_log' => 'order_id: '. $order->getId(),
                            'response' => json_encode($this->getRequest()->getParams())
                        ];
                    }
                    $this->emailError->sendCheckoutReportEmail($errorData, $order->getStoreId());
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

            if (!isset($orderId) || isset($order) && !$order->getId()) {
                $this->laybuy->laybuyCancel($token);
            }

            return $this->_redirect('checkout/cart', ['_secure' => true]);
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);
    }
}
