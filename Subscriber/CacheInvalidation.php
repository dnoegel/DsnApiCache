<?php

namespace Shopware\DsnApiCache\Subscriber;

class CacheInvalidation implements \Enlight\Event\SubscriberInterface
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
            'Shopware_Plugins_HttpCache_ClearCache' => 'onClearCache',
            'Shopware_CronJob_ClearHttpCache' => 'onClearCache',
            'Shopware_Plugins_HttpCache_InvalidateCacheId' => 'onInvalidateCacheId',
        );
    }

    public function onInvalidateCacheId(\Enlight_Event_EventArgs $args)
    {
        $cacheId = $args->get('cacheId');
        if (!$cacheId) {
            $args->setReturn(false);
            return;
        }

        $args->setReturn($this->cache->invalidateCacheId($cacheId));
    }

    public function onClearCache()
    {
        $this->cache->invalidateCache();
    }
}