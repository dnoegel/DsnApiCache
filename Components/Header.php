<?php

namespace Shopware\DsnApiCache\Components;

class Header
{
    public function getCacheControllers()
    {
        return array(
            'articles/get' => 3600,
            'articles/index' => 3600,
            'categories/get' => 3600,
            'categories/index' => 3600,
        );
    }


    public function setCacheHeaders(\Enlight_Controller_Action $controller)
    {
        $request = $controller->Request();
        $response = $controller->Response();
        $controllerName = strtolower(strtolower($request->getControllerName() . '/' . $request->getActionName()));
        
        if ($request->getHeader('Surrogate-Capability') === false) {
            return;
        }
       
        /**
         * Emits Shopware_Plugins_HttpCache_ShouldNotCache Event
         */
        if (Enlight()->Events()->notifyUntil(
            'Shopware_Plugins_ApiCache_ShouldNotCache',
            array(
                'subject' => $this,
                'action' => $request->getActionName(),
                'controller' => $request->getControllerName(),
            )
        )
        ) {
            return;
        }

        $cacheControllers = $this->getCacheControllers();
        if (!isset($cacheControllers[$controllerName])) {
            return;
        }
        
        if ($response->isRedirect()) {
            $response->setHeader('Cache-Control', 'private, no-cache');
            return;
        }

        $cacheTime = (int)$cacheControllers[$controllerName];
        $request->setParam('__cache', $cacheTime);
        $response->setHeader('Cache-Control', 'public, max-age=' . $cacheTime . ', s-maxage=' . $cacheTime, true);

        $cacheIds = $this->getCacheIdsFromController($controller);
        $cacheIds = Enlight()->Events()->filter(
            'Shopware_Plugins_ApiCache_GetCacheIds',
            $cacheIds,
            array('subject' => $this, 'action' => $controller)
        );

        if (empty($cacheIds)) {
            return;
        }

        $cacheIds = ';' . implode(';', $cacheIds) . ';';
        $response->setHeader('x-shopware-cache-id', $cacheIds);
    }


    /**
     * Returns an array of affected cacheids for this $controller
     *
     * @param \Enlight_Controller_Action $controller
     * @return array
     */
    public function getCacheIdsFromController(\Enlight_Controller_Action $controller)
    {
        $request = $controller->Request();
        $view = $controller->View();
        $controllerName = strtolower($request->getControllerName() . '/' . $request->getActionName());
        $cacheIds = array();

        switch ($controllerName) {
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

        return $cacheIds;
    }

}