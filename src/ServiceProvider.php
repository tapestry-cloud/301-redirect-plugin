<?php

namespace TapestryCloud\Redirect;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Tapestry\Entities\Configuration;

class ServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /** @var array */
    protected $provides = [];

    /**
     * Use the register method to register items with the container via the
     * protected $this->container property or the `getContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        // ...
    }

    /**
     * Method will be invoked on registration of a service provider implementing
     * this interface. Provides ability for eager loading of Service Providers.
     *
     * @return void
     * @throws \Exception
     */
    public function boot()
    {
        /** @var Engine $engine */
        $engine = $this->getContainer()->get(Engine::class);

        /** @var Configuration $configuration */
        $configuration = $this->getContainer()->get(Configuration::class);

        // Place your plugin bootstrapping here...
    }
}