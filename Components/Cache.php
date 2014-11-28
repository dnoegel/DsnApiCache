<?php

namespace Shopware\DsnApiCache\Components;

/**
 * Will set cache headers depending on the controller
 *
 * Class Cache
 * @package Shopware\DsnApiCache\Components
 */
class Cache
{
    /**
     * @var \Enlight_Event_EventManager
     */
    private $eventManager;
    private $cacheControllers;

    public function __construct(\Enlight_Event_EventManager $eventManager, $cacheControllers)
    {
        $this->eventManager = $eventManager;
        $this->cacheControllers = $cacheControllers;
    }


    /**
     * Will set cache headers for the given controller
     *
     * @param \Enlight_Controller_Action $controller
     */
    public function setCacheHeaders(\Enlight_Controller_Action $controller)
    {
        $route = $this->getRoute($controller);

        if ($this->isCacheable($controller)) {
            return;
        }

        $cacheTime = $this->cacheControllers[$route];
        $controller->Request()->setParam('__cache', $cacheTime);
        $controller->Response()->setHeader('Cache-Control', 'public, max-age=' . $cacheTime . ', s-maxage=' . $cacheTime, true);

        $cacheIds = $this->getCacheIdsFromController($controller);

        if (empty($cacheIds)) {
            return;
        }

        $cacheIds = ';' . implode(';', $cacheIds) . ';';
        $controller->Response()->setHeader('x-shopware-cache-id', $cacheIds);
    }

    /**
     * Check if the given controller is cacheable
     *
     * @param \Enlight_Controller_Action $controller
     * @return bool
     */
    private function isCacheable(\Enlight_Controller_Action $controller)
    {
        $route = $this->getRoute($controller);

        // not in whitelist
        if (!isset($this->cacheControllers[$route])) {
            return false;
        }

        if ($controller->Request()->getHeader('Surrogate-Capability') === false) {
            return false;
        }

        // allow plugin intervention
        if ($this->eventManager->notifyUntil(
            'Shopware_Plugins_ApiCache_ShouldNotCache',
            array(
                'subject' => $this,
                'action' => $controller->Request()->getActionName(),
                'controller' => $controller->Request()->getControllerName(),
            )
        )
        ) {
            return false;
        }

        // don't cache redirects
        if ($controller->Response()->isRedirect()) {
            $controller->Response()->setHeader('Cache-Control', 'private, no-cache');
            return false;
        }

        return true;
    }

    /**
     * Return a normalized route for the given controller
     *
     * @param \Enlight_Controller_Action $controller
     * @return string
     */
    private function getRoute(\Enlight_Controller_Action $controller)
    {
        return strtolower(strtolower($controller->Request()->getControllerName() . '/' . $controller->Request()->getActionName()));
    }

    /**
     * Returns an array of affected cacheids for $controller
     *
     * @param \Enlight_Controller_Action $controller
     * @return array
     */
    private function getCacheIdsFromController(\Enlight_Controller_Action $controller)
    {
        $route = $this->getRoute($controller);
        $view = $controller->View();
        $cacheIds = array();

        switch ($route) {
            case 'articles/index':
                foreach ($view->getAssign('data') as $article) {
                    $cacheIds[] = 'a' . $article['id'];
                }

                break;
            case 'articles/get':
                $article = $view->getAssign('data');
                if ($article) {
                    $cacheIds[] = 'a' . $article['id'];
                }

                break;
            case 'categories/index':
                foreach ($view->getAssign('data') as $category) {
                    $cacheIds[] = 'c' . $category['id'];
                }

                break;
            case 'categories/get':
                $category = $view->getAssign('data');
                if ($category) {
                    $cacheIds[] = 'c' . $category['id'];
                }

                break;
        }

        return $this->eventManager->filter(
            'Shopware_Plugins_ApiCache_GetCacheIds',
            $cacheIds,
            array('subject' => $this, 'action' => $controller)
        );
    }

}