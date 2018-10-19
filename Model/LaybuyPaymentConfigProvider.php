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
     * LaybuyPaymentConfigProvider constructor.
     * @param Laybuy $laybuyModel
     */
    public function __construct(
        Laybuy $laybuyModel
    )
    {
        $this->laybuyModel = $laybuyModel;
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
                    'logoSrc' => $this->laybuyModel->getLaybuyLogoSrc()
                ]
            ]
        ];

        return $config;
    }
}