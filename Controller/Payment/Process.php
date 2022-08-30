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

    protected $checkoutSession;

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
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->laybuy = $laybuyFactory->create();
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        header('Access-Control-Allow-Origin: https://magento-venia-concept-nvls2.local.pwadev:8368');

        $this->logger->debug([__METHOD__ => 'start']);

        $isPWA = $this->getRequest()->getParam('fromPwa');
        $guest_email = $this->getRequest()->getParam('guest-email');
        $cartId = $this->getRequest()->getParam('cartId');
        $orderId = $this->getRequest()->getParam('orderId');


        if($isPWA == true) {
            if (!empty($orderId)) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);

                if ($order) {
                    $order_data = [];
                    /*get customer details*/

                    $order_data['customer_lastname'] = $order->getCustomerLastname();
                    $order_data['customer_firstname'] = $order->getCustomerFirstname();
                    $order_data['customer_email'] = $order->getCustomerEmail();

                    /* get shipping details */

                    $order_data['shipping_method'] = $order->getShippingDescription();
                    $shipping_address = $order->getShippingAddress();
                    $order_data['shipping_city'] = $shipping_address->getCity();
                    $order_data['shipping_street'] = $shipping_address->getStreet();
                    $order_data['shipping_postcode'] = $shipping_address->getPostcode();
                    $order_data['shipping_state_code'] = $shipping_address->getRegionCode();
                    $order_data['shipping_country_code'] = $shipping_address->getCountryId();
                    $order_data['shipping_state'] = $shipping_address->getRegion();

                    /* get  total */
                    $tax_amount=$order->getTaxAmount();
                    $total=$order->getGrandTotal();

                    return $this->jsonFactory->create()->setData(['success' => true, 'details' => $order_data]);
                }

                return $this->jsonFactory->create()->setData(['success' => false, 'details' => []]);
            }

            if (!empty($cartId)) {
                $redirectUrl = $this->laybuy->getLaybuyRedirectUrl($guest_email, true, $cartId);
                return $this->jsonFactory->create()->setData(['success' => true, 'redirect_url' => $redirectUrl]);
            }
        }

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
