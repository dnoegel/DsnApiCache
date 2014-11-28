<?php

use Shopware\Components\Model\ModelManager;
use Shopware\DsnApiCache\Components\CacheInvalidator\Http;
use Shopware\DsnApiCache\Components\CacheInvalidator\Null;

class Shopware_Plugins_Core_DsnApiCache_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    public function getLabel()
    {
        return 'DsnApiCache';
    }

    public function uninstall()
    {
        return true;
    }

    public function update($oldVersion)
    {
        return true;
    }

    public function install()
    {
        if (!$this->assertVersionGreaterThen('4.2.0')) {
            throw new \RuntimeException('At least Shopware 4.2.0 is required');
        }


        $this->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );

        return true;
    }

    /**
     * This callback function is triggered at the very beginning of the dispatch process and allows
     * us to register additional events on the fly. This way you won't ever need to reinstall you
     * plugin for new events - any event and hook can simply be registerend in the event subscribers
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $this->registerMyComponents();
        $this->registerCustomModels();

        $request = $args->get('request');
        $cache = $this->getCacheService($request);

        $config = new \Shopware\DsnApiCache\Structs\Config();
        $config->proxyPrune = false;

        $subscribers = array(
            new \Shopware\DsnApiCache\Subscriber\CacheInvalidation($cache),
        );

        if ($config->proxyPrune) {
            $subscribers[] = new \Shopware\DsnApiCache\Subscriber\Lifecycle($cache);
        }

        if ($request->getModuleName() == 'api') {
            $subscribers[] = new \Shopware\DsnApiCache\Subscriber\Cache($cache);
        }

        foreach ($subscribers as $subscriber) {
            $this->Application()->Events()->addSubscriber($subscriber);
        }
    }

    private function getCacheService(
        Enlight_Controller_Request_RequestHttp $request
    )
    {
        $canInvalidate = $this->config->proxyPrune && $this->config->proxy !== null && $request && $this->request->getHeader('Surrogate-Capability') !== false;

        $invalidator = $canInvalidate ? $this->getHttpInvalidator($request) : new Null();

        return new \Shopware\DsnApiCache\Components\CacheFacade(
            new \Shopware\DsnApiCache\Structs\Config(),
            $invalidator,
            new \Shopware\DsnApiCache\Components\Header()
        );
    }

    public function getHttpInvalidator($request)
    {
        return new Http(new \Zend_Http_Client($this->getProxyUrl($request), array(
            'useragent' => 'Shopware/' . \Shopware::VERSION,
            'timeout' => 5,
        )));
    }

    public function registerMyComponents()
    {
        $this->Application()->Loader()->registerNamespace(
            'Shopware\DsnApiCache',
            $this->Path()
        );
    }


    /**
     * Returns the configured proxy-url.
     *
     * Fallbacks to autodetection if proxy-url is not configured and $request is given.
     * Returns null if $request is not given or autodetection fails.
     *
     * @param Enlight_Controller_Request_RequestHttp $request
     * @return string|null
     */
    public function getProxyUrl(\Enlight_Controller_Request_RequestHttp $request = null)
    {
        $proxyUrl = trim($this->Config()->get('proxy'));
        if (!empty($proxyUrl)) {
            return $proxyUrl;
        };

        // if proxy url is not set fall back to host detection
        if ($request !== null && $request->getHttpHost()) {
            return $request->getScheme() . '://'
            . $request->getHttpHost()
            . $request->getBaseUrl() . '/';
        }

        /** @var ModelManager $em */
        $em = $this->get('models');
        $repository = $em->getRepository('Shopware\Models\Shop\Shop');

        /** @var Shopware\Models\Shop\Shop $shop */
        $shop = $repository->findOneBy(array('default' => true));

        if (!$shop->getHost()) {
            return null;
        }

        $url = sprintf(
            '%s://%s%s/',
            'http',
            $shop->getHost(),
            $shop->getBasePath()
        );

        return $url;
    }
}