<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Base;

use App\Entity\ConstructionManager;
use App\Security\Model\UserToken;
use App\Security\Voter\Base\BaseVoter;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BaseController extends AbstractController
{
    /**
     * @var ConstructionManager[]
     */
    private $userCache = [];

    public static function getSubscribedServices()
    {
        return parent::getSubscribedServices() + ['session' => '?' . SessionInterface::class, 'doctrine' => '?' . ManagerRegistry::class];
    }

    /**
     * @param string $message the translation message to display
     * @param string $link
     */
    protected function displayError($message, $link = null)
    {
        $this->displayFlash('danger', $message, $link);
    }

    /**
     * @param string $message the translation message to display
     * @param string $link
     */
    protected function displaySuccess($message, $link = null)
    {
        $this->displayFlash('success', $message, $link);
    }

    /**
     * @param string $message the translation message to display
     * @param string $link
     */
    protected function displayDanger($message, $link = null)
    {
        $this->displayFlash('danger', $message, $link);
    }

    /**
     * @param string $message the translation message to display
     * @param string $link
     */
    protected function displayInfo($message, $link = null)
    {
        $this->displayFlash('info', $message, $link);
    }

    /**
     * @return ConstructionManager
     */
    protected function getUser()
    {
        /** @var UserToken $user */
        $user = parent::getUser();

        if ($user === null) {
            return null;
        }

        // early return if found in cache
        if (isset($this->userCache[$user->getUsername()])) {
            return $this->userCache[$user->getUsername()];
        }

        // load & cache
        $constructionManager = $this->get('doctrine')->getRepository('App:ConstructionManager')->findOneBy(['email' => $user->getUsername()]);
        $this->userCache[$user->getUsername()] = $constructionManager;

        return $constructionManager;
    }

    /**
     * @param $entity
     */
    protected function ensureAccess($entity)
    {
        $this->denyAccessUnlessGranted(BaseVoter::ANY_ATTRIBUTE, $entity);
    }

    /**
     * Renders a view.
     *
     * @final
     *
     * @param string        $view
     * @param array         $parameters
     * @param Response|null $response
     *
     * @return Response
     */
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $constructionManager = $this->getUser();
        if ($constructionManager !== null && $constructionManager->getActiveConstructionSite() !== null) {
            $parameters = $parameters + ['constructionSiteName' => $constructionManager->getActiveConstructionSite()->getName()];
        }

        return parent::render($view, $parameters, $response);
    }

    /**
     * @param $type
     * @param $message
     * @param string $link
     */
    private function displayFlash($type, $message, $link = null)
    {
        if ($link !== null) {
            $message = '<a href="' . $link . '">' . $message . '</a>';
        }
        $this->get('session')->getFlashBag()->set($type, $message);
    }
}
