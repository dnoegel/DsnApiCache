<?php

namespace Shopware\DsnApiCache\Components\CacheInvalidator;

use Shopware\DsnApiCache\Components\CacheInvalidator;

class Http implements CacheInvalidator
{
    /**
     * @var \Zend_Http_Client
     */
    private $client;

    public function __construct(\Zend_Http_Client $client)
    {
        $this->client = $client;
    }


    public function invalidate($id = null)
    {
        if ($id) {
            $this->client->setHeaders('x-shopware-invalidates', $id);
        }

        try {
            $this->client->request('BAN');
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

}