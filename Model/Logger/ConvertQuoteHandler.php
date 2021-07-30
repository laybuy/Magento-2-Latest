<?php

namespace Laybuy\Laybuy\Model\Logger;

use Laybuy\Laybuy\Model\Logger\ConvertQuoteLogger;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class ConvertQuoteHandler
 */
class ConvertQuoteHandler extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = ConvertQuoteLogger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/laybuy_convert_quote.log';
}
