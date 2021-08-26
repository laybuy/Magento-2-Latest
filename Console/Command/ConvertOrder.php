<?php

namespace Laybuy\Laybuy\Console\Command;

use Laybuy\Laybuy\Model\Laybuy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\App\State;
use Laybuy\Laybuy\Model\LaybuyConvertOrder;
use Laybuy\Laybuy\Cron\Order\Type\Capture as OrderTypeCapture;
use Laybuy\Laybuy\Cron\Order\Type\Order as OrderTypeOrder;

/**
 * Class ConvertOrder
 */
class ConvertOrder extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var LaybuyConvertOrder
     */
    protected $laybuyConvertOrder;

    /**
     * @var OrderTypeCapture
     */
    protected $orderTypeCapture;

    /**
     * @var OrderTypeOrder
     */
    protected $orderTypeOrder;

    /**
     * ConvertOrder constructor.
     * @param State $state
     * @param LaybuyConvertOrder $laybuyConvertOrder
     * @param OrderTypeCapture $orderTypeCapture
     * @param OrderTypeOrder $orderTypeOrder
     * @param null $name
     */
    public function __construct(
        State $state,
        LaybuyConvertOrder $laybuyConvertOrder,
        OrderTypeCapture $orderTypeCapture,
        OrderTypeOrder $orderTypeOrder,
        $name = null
    ) {
        $this->state = $state;
        $this->laybuyConvertOrder = $laybuyConvertOrder;
        $this->orderTypeCapture = $orderTypeCapture;
        $this->orderTypeOrder = $orderTypeOrder;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('laybuy:laybuy:convert-order');
        $this->setDescription('Auto Convert Order');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('--- Start Process ---');
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
        }
        $this->orderTypeCapture->executeCommand();
        $this->orderTypeOrder->executeCommand();
        $output->writeln('--- End Process ---');

    }
}
