<?php

use Shopware\Components\Model\ModelManager;
use Shopware\DsnApiCache\Components\CacheInvalidator\Http;
use Shopware\DsnApiCache\Components\CacheInvalidator\Null;
use Shopware\DsnApiCache\Components\Header;
use Shopware\DsnApiCache\Subscriber\Cache;

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


        $form = $this->Form();
        $form->setElement('textarea', 'apiCacheControllers', array(
            'label' => 'Caching von API-Resourcen',
            'value' =>
                "articles/get 3600\r\n" .
                "articles/index 3600\r\n" .
                "categories/get 3600\r\n" .
                "categories/index 3600\r\n"
        ));

        return true;
    }

    /**
     * This callback function is triggered at the very beginning of the dispatch process and allows
     * us to register additional events on the fly. This way you won't ever need to reinstall you
     * plugin for new events - any event and hook can simply be registerend in the event subscribers
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $request = $args->get('request');

        if ($request->getModuleName() != 'api') {
            return;
        }

        $this->registerMyComponents();

        $this->Application()->Events()->addSubscriber($this->getCacheSubscriber());
    }

    /**
     * Read cache controllers from config and prepare them a bit
     *
     * @return array
     */
    private function getCacheControllers()
    {
        $controllers = $this->Config()->get('apiCacheControllers');
        if (empty($controllers)) {
            return array();
        }

        $result = array();
        $controllers = str_replace(array("\r\n", "\r"), "\n", $controllers);
        $controllers = explode("\n", trim($controllers));
        foreach ($controllers as $controller) {
            list($controller, $cacheTime) = explode(" ", $controller);
            $result[strtolower($controller)] = (int) $cacheTime;
        }

        return $result;
    }

    public function registerMyComponents()
    {
        $this->Application()->Loader()->registerNamespace(
            'Shopware\DsnApiCache',
            $this->Path()
        );
    }

    /**
     * @return Cache
     */
    private function getCacheSubscriber()
    {
        return new Cache(
            new \Shopware\DsnApiCache\Components\Cache(
                $this->get('events'),
                $this->getCacheControllers()
            )
        );
    }
}