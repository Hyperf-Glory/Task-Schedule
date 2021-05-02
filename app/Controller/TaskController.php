<?php

declare(strict_types=1);


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
