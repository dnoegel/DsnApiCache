<?php

namespace Shopware\DsnApiCache\Subscriber;

class Cache implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var \Shopware\DsnApiCache\Components\CacheFacade
     */
    private $cache;

    public function __construct(\Shopware\DsnApiCache\Components\CacheFacade $cache)
    {
        $this->cache = $cache;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure' => 'onPostDispatch'
        );
    }


    /**
     * On post dispatch we try to find affected articleIds displayed during this request
     *
     * @param \Enlight_Controller_EventArgs $args
     */
    public function onPostDispatch(\Enlight_Controller_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->getSubject();

        $this->cache->setCacheHeaders($controller);
    }


}