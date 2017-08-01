<?php

namespace TapestryCloud\Redirect\Tests;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;
use Tapestry\Console\DefaultInputDefinition;
use Tapestry\Entities\Project;
use Tapestry\Generator;
use Tapestry\Tapestry;
use Tapestry\Tests\CommandTestBase;

class CompileTest extends CommandTestBase
{
    public static function setUpBeforeClass()
    {
        self::$tmpPath = __DIR__ . DIRECTORY_SEPARATOR . '_tmp';
        $fileSystem = new Filesystem();
        $fileSystem->mkdir(self::$tmpPath);
        chdir(self::$tmpPath);
        self::$fileSystem = $fileSystem;
    }

    protected function copyDirectory($from, $to)
    {
        $from = __DIR__ . DIRECTORY_SEPARATOR . $from;
        $to = __DIR__ . DIRECTORY_SEPARATOR . $to;
        $directoryContent = new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($directoryContent, \RecursiveIteratorIterator::CHILD_FIRST);
        /** @var \SplFileInfo $item */
        foreach ($files as $item) {
            if ($item->isDir()) {
                self::$fileSystem->mkdir(str_replace($from, $to, $item->getPath()));
            } else {
                self::$fileSystem->copy($item->getPathname(), str_replace($from, $to, $item->getPathname()));
            }
        }
    }

    private function bootstrapTapestry()
    {
        // <Bootstrap Tapestry>
        $definitions = new DefaultInputDefinition();

        $tapestry = new Tapestry(new ArrayInput([
            '--site-dir' => __DIR__ . DIRECTORY_SEPARATOR . '_tmp',
            '--env' => 'testing'
        ], $definitions));
        $generator = new Generator($tapestry->getContainer()->get('Compile.Steps'), $tapestry);

        /** @var Project $project */
        $project = $tapestry->getContainer()->get(Project::class);
        $project->set('cmd_options', []);
        $generator->generate($project, new NullOutput);
        // </Bootstrap Tapestry>
    }

    function testPlugin() {
        $this->copyDirectory('assets/build_test_1/src', '_tmp');
        $this->bootstrapTapestry();
        $this->assertFileEquals(__DIR__ . '/assets/build_test_1/check/nginx_redirects_first.conf', __DIR__ . '/_tmp/nginx_redirects.conf');

        // Modify permalinks to prompt redirect mapping
        self::$fileSystem->copy(__DIR__ . '/_tmp/config.php', __DIR__ . '/_tmp/config.old.php', true);
        self::$fileSystem->copy(__DIR__ . '/_tmp/config.new.php', __DIR__ . '/_tmp/config.php', true);
        $this->bootstrapTapestry();
        $this->assertFileEquals(__DIR__ . '/assets/build_test_1/check/nginx_redirects_second.conf', __DIR__ . '/_tmp/nginx_redirects.conf');

        // Modify permalinks once more to reset back to before, this tests the mapping works when changes are reverted
        self::$fileSystem->copy(__DIR__ . '/_tmp/config.old.php', __DIR__ . '/_tmp/config.php', true);
        $this->bootstrapTapestry();
        $this->assertFileEquals(__DIR__ . '/assets/build_test_1/check/nginx_redirects_third.conf', __DIR__ . '/_tmp/nginx_redirects.conf');
    }
}
