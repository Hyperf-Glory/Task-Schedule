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
 * @Constants
 * @method  static getMessage(int $code)
 */
class ErrorCode extends AbstractConstants
{
    //基本错误码 0～1000
    public const AUTH_ERROR = 401;

    /**
     * @Message("Server Error")
     */
    public const SERVER_ERROR = 500;
}
