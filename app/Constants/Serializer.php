<?php
declare(strict_types = 1);

namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * Class Serializer
 * @package App\Constants
 * @Constants()
 */
class Serializer extends AbstractConstants
{
    /**
     * @const string
     */
    public const SERIALIZER_TYPE_PHP = 'php_serializer';

    /**
     * @const string
     */
    public const SERIALIZER_TYPE_CLOSURE = 'closure_serializer';
}
