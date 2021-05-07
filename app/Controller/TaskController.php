<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Controller;

use Hyperf\View\Render;
use Psr\Http\Message\ResponseInterface;

class TaskController extends AbstractController
{
    public function index(Render $render): ResponseInterface
    {
        return $render->render('task');
    }
}
