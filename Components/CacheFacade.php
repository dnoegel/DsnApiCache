<?php

namespace Shopware\DsnApiCache\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\DsnApiCache\Structs\Config;

class CacheFacade
{
    /**
     * @var \Shopware\DsnApiCache\Structs\Config
     */
    private $config;

    /**
     * @var CacheInvalidator
     */
    private $cacheInvalidator;
    /**
     * @var Header
     */
    private $headerService;

    public function __construct(
        Config $config,
        CacheInvalidator $cacheInvalidator,
        Header $headerService
    )
    {
        $this->config = $config;
        $this->cacheInvalidator = $cacheInvalidator;
        $this->headerService = $headerService;
    }

    /**
     * Invalidates a given $cacheId
     *
     * This sends a http-ban-request to the proxyUrl containing
     * the $cacheId in the x-shopware-invalidates http-header
     *
     * @param string $cacheId
     * @return bool
     */
    public function invalidateCacheId($cacheId)
    {
        return $this->cacheInvalidator->invalidate($cacheId);
    }

    public function invalidateCache()
    {
        return $this->cacheInvalidator->invalidate();
    }

    public function setCacheHeaders(\Enlight_Controller_Action $controller)
    {
        $this->headerService->setCacheHeaders($controller);
    }
}