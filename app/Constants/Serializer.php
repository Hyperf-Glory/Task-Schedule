<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * Class Serializer.
 * @Constants
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
