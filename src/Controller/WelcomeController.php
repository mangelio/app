<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Controller\Base\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/welcome")
 */
class WelcomeController extends BaseController
{
    /**
     * @Route("/welcome", name="welcome")
     *
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('welcome/index.html.twig');
    }
}
