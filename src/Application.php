<?php

namespace Example;

use Nice\Application as BaseApplication;
use Symfony\Component\Config\Loader\LoaderInterface;

class Application extends BaseApplication
{
    protected function registerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/../config.yml');
    }
}
