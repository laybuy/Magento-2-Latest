<?php

namespace Laybuy\Laybuy\Model;

use Laybuy\Laybuy\Model\Config;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Psr\Log\LoggerInterface;

/**
 * Class Email
 */
class Email
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Email constructor.
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        Config $config,
        LoggerInterface $logger
    )
    {
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->config = $config;
        $this->logger = $logger;
    }


    /**
     * @param $data
     * @param $storeId
     */
    public function sendEmail($data, $storeId)
    {
        try {
            if (!$this->getEnableEmailReport($storeId)) {
                return;
            }
            $adminEmail = $this->config->getPaymentErrorEmail($storeId);
            if (!$adminEmail) {
                return;
            }
            $store = $this->storeManager->getStore($storeId);
            $transport = $this->transportBuilder->setTemplateIdentifier(
                $this->config->getEmailTemplate($storeId)
            )->setTemplateOptions(
                ['area' => Area::AREA_FRONTEND, 'store' => $store->getId()]
            )->setTemplateVars(
                [
                    'store' => $store,
                    'report_data' => $data,
                ]
            )->setFromByScope(
                $this->config->getEmailIdentity($storeId),
                $storeId
            )->addTo(
                $adminEmail
            )->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Can not send laybuy cron report', [$e->getMessage(), $e->getTraceAsString()]);
        }
    }

    /**
     * @param $data
     */
    public function sendEmailListByStore($data)
    {
        foreach ($data as $storeId => $storeData) {
            $this->sendEmail($storeData, $storeId);
        }
    }

    /**
     * @param $storeId
     * @return mixed|null
     */
    public function getEnableEmailReport($storeId)
    {
        return $this->config->enableEmailReport($storeId);
    }
}
