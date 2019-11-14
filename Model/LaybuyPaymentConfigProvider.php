<?php

namespace Laybuy\Laybuy\Model;

use \Magento\Checkout\Model\ConfigProviderInterface;


class LaybuyPaymentConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Laybuy
     */
    protected $laybuyModel;

    /**
     * @var Laybuy
     */
    protected $laybuyConfig;

    /**
     * LaybuyPaymentConfigProvider constructor.
     * @param Laybuy $laybuyModel
     * @param Config $laybuyConfig
     */
    public function __construct(
        Laybuy $laybuyModel,
        Config $laybuyConfig
    )
    {
        $this->laybuyModel = $laybuyModel;
        $this->laybuyConfig = $laybuyConfig;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'laybuy_payment' => [
                    'isActive' => $this->laybuyModel->isActive(),
                    'paymentAction' => $this->laybuyModel->getConfigPaymentAction(),
                    'logoSrc' => $this->laybuyModel->getLaybuyLogoSrc(),
                    'laybuyProcessUrl' => $this->laybuyModel->getLaybuyProcessUrl(),
                    'checkoutPaySrc' => $this->laybuyConfig->getMagentoAssetUrl('checkout/pay.jpg'),
                    'checkoutScheduleSrc' => $this->laybuyConfig->getMagentoAssetUrl('checkout/schedule.jpg'),
                    'checkoutCompleteSrc' => $this->laybuyConfig->getMagentoAssetUrl('checkout/complete.jpg'),
                    'checkoutDoneSrc' => $this->laybuyConfig->getMagentoAssetUrl('checkout/done.jpg'),
                ]
            ]
        ];

        return $config;
    }
}