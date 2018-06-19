<?php

/*
 * This file is part of the nodika project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Controller\Base\BaseFormController;
use App\Entity\FrontendUser;
use App\Form\Model\ContactRequest\ContactRequestType;
use App\Model\ContactRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaticController extends BaseFormController
{
    /**
     * @Route("/", name="static_index")
     *
     * @return Response
     */
    public function indexAction()
    {
        if ($this->getUser() instanceof FrontendUser) {
            return $this->redirectToRoute('frontend_dashboard_index');
        }

        return $this->render('static/index.html.twig');
    }
}
