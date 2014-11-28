<?php

namespace Shopware\DsnApiCache\Components\CacheInvalidator;

use Shopware\DsnApiCache\Components\CacheInvalidator;

class Null implements CacheInvalidator
{
    public function invalidate($id = null)
    {
        return false;
    }

}