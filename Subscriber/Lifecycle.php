<?php

namespace Shopware\DsnApiCache\Subscriber;

use Shopware\DsnApiCache\Components\CacheFacade;

class Lifecycle implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var CacheFacade
     */
    private $cache;

    public function __construct(CacheFacade $cache)
    {
        $this->cache = $cache;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'Shopware\Models\Article\Article::postUpdate' => 'onPostPersist',
            'Shopware\Models\Article\Article::postPersist' => 'onPostPersist',

            'Shopware\Models\Category\Category::postPersist' => 'onPostPersist',
            'Shopware\Models\Category\Category::postUpdate' => 'onPostPersist'
        );
    }

    /**
     * Cache invalidation based on model events
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onPostPersist(\Enlight_Event_EventArgs $eventArgs)
    {
        $entity = $eventArgs->get('entity');
        if ($entity instanceof \Doctrine\ORM\Proxy\Proxy) {
            $entityName = get_parent_class($entity);
        } else {
            $entityName = get_class($entity);
        }

        $cacheIds = array();

        switch ($entityName) {
            case 'Shopware\Models\Article\Article':
                $cacheIds[] = 'a' . $entity->getId();
                break;
            case 'Shopware\Models\Category\Category':
                $cacheIds[] = 'c' . $entity->getId();
                break;
        }

        foreach ($cacheIds as $cacheId) {
            $this->cache->invalidateCacheId($cacheId);
        }
    }
}