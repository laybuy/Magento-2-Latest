<?php

namespace Laybuy\Laybuy\Model\Resolver\DataProvider;

use Laybuy\Laybuy\Model\Logger\Logger;
use Laybuy\Laybuy\Model\LaybuyFactory;

class LaybuyDataProvider
{
    protected $logger;

    /**
     * @var \Laybuy\Laybuy\Model\Laybuy
     */
    protected $laybuy;

    public function __construct(
        Logger $logger,
        LaybuyFactory $laybuyFactory
    )
    {
        $this->laybuy = $laybuyFactory->create();
        $this->logger = $logger;
    }

    public function getData($args)
    {
		$this->logger->debug($args);
        try {
            $this->logger->debug([__METHOD__ => 'start']);

            $redirectUrl = $this->laybuy->getLaybuyRedirectUrl($args['email'], true, $args['cartId']);

            if ($redirectUrl) {
                $this->logger->debug([__METHOD__ . '  LAYBUY REDIRECT URL ' => $redirectUrl]);

                return ['success' => true, 'redirect_url' => $redirectUrl];
            }

            $this->logger->debug([__METHOD__ . '  LAYBUY STATUS ' => 'FAILED']);

            return ['success' => true, 'redirect_url' => ''];

        } catch (\Exception $e) {
            $this->logger->debug([__METHOD__ . '  LAYBUY STATUS ' => 'FAILED']);
            $this->logger->debug([__METHOD__ . '  LAYBUY ERROR ' => $e->getMessage()]);
            return ['success' => true, 'redirect_url' => ''];
        }
    }
}
