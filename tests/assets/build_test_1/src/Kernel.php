<?php

namespace SiteOne;

use Tapestry\Modules\Kernel\KernelInterface;
use Tapestry\Tapestry;

class Kernel implements KernelInterface
{
    /**
     * @var Tapestry
     */
    private $tapestry;

    /**
     * DefaultKernel constructor.
     *
     * @param Tapestry $tapestry
     */
    public function __construct(Tapestry $tapestry)
    {
        $this->tapestry = $tapestry;
    }

    /**
     * This method is executed by Tapestry when the Kernel is registered.
     *
     * @return void
     */
    public function register()
    {
        // ...
    }

    /**
     * This method of executed by Tapestry as part of the build process.
     *
     * @return void
     */
    public function boot()
    {
        $this->tapestry->register(\TapestryCloud\Redirect\ServiceProvider::class);
    }
}
