<?php

namespace Shopware\DsnApiCache\Components;

interface CacheInvalidator
{
    public function invalidate($id = null);
}