<?php

namespace TapestryCloud\Redirect;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Tapestry\Entities\Cache;
use Tapestry\Entities\Filesystem\FileAction;
use Tapestry\Entities\Project;
use Tapestry\Tapestry;

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
        /** @var Tapestry $tapestry */
        $tapestry = $this->getContainer()->get(Tapestry::class);

        /** @var Project $compiled */
        $project = $tapestry->getContainer()->get(Project::class);

        //
        // Identify Redirects.
        // This looks up all compiled permalinks and matches them with those stored from the previous run.
        // If a file's permalink has changed it then adds a Nginx 301 redirect rule to nginx_redirects.conf.
        // This is supposed to be used for when permalinks have been modified on files that have been published long
        // enough to appear in search indexes.
        //
        // ///////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // Permalink Cache data structure:
        // [
        //      ...,
        //      'k17a6shajuqyah1' => [
        //          'current' => '/some/page/index.html',
        //          'history' => []
        //      ],
        //      ...,
        // ]
        //
        // 1. If current page uid does not exist in the Permalink cache then add a new entry.
        // 2. If current page uid exists:
        //    2.a. and the current permalink equals the permalink stored as current, continue the loop.
        //    2.b. and the current permalink does not equal the current value, push the current value to
        //         the history array and set the current value to the current permalink.
        //         2.b.i. If the current permalink is in the history array remove it
        //
        //  3. Foreach page history item:
        //     3.a. If history item doesn't equal current item, write out to nginx config stub a redirect line
        //          from the history item to the current item.
        //
        // ///////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // The above should ensure that if a file changes its permalink multiple times, each published case will be
        // recorded in a history and have a specific redirect written for it. If for some reason a file's permalink
        // changes back to equal one in its history then the historical redirect will be deleted ensuring no redirect
        // loops are generated.
        //
        $tapestry->getEventEmitter()->addListener('compile.after', function () use ($tapestry, $project) {
            $cache = new Cache($project->currentWorkingDirectory . DIRECTORY_SEPARATOR . '.'. $project->environment .'_301_cache', 'permanent');
            $cache->load();
            $redirects = [];

            /** @var FileAction $file */
            foreach ($project['compiled'] as $file) {
                $currentPermalink = $file->getFile()->getCompiledPermalink();
                $fileIdentifier = $file->getFile()->getUid();

                if ($item = $cache->getItem($fileIdentifier)) {
                    // 2b:
                    if ($item['current'] !== $currentPermalink) {
                        array_push($item['history'], $item['current']);
                        $item['current'] = $currentPermalink;

                        // 2bi:
                        $item['history'] = array_filter($item['history'], function($v) use ($item) {
                            return $v !== $item['current'];
                        });
                    }
                } else {
                    // 1:
                    $item = [
                        'current' => $currentPermalink,
                        'history' => []
                    ];
                }

                if (count($item['history']) > 0) {
                    foreach ($item['history'] as $from) {
                        array_push($redirects, 'rewrite ^/'. $from .'$ '. url($item['current']) .' permanent;');
                    }
                }
                $cache->setItem($fileIdentifier, $item);
            }

            $cache->save();
            file_put_contents($project->currentWorkingDirectory . DIRECTORY_SEPARATOR . 'nginx_redirects.conf', implode("\n", $redirects));
        });
    }
}