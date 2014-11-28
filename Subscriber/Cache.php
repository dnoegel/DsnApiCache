<?php

namespace Shopware\DsnApiCache\Subscriber;

use Shopware\DsnApiCache\Components;

class Cache implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var Components\Cache
     */
    private $cache;

    public function __construct(Components\Cache $cache)
    {
        $this->header = $cache;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure_Api' => 'onPostDispatch'
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