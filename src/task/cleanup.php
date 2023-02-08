<?php declare(strict_types=1);

namespace Friemt\Deployer\Tasks;

use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\within;
use function Deployer\writeln;

set('cleanup_paths', ['var/cache']);

task('cleanup:paths', function (): void {
    $currentRelease = basename(get('release_or_current_path'));
    $releases = get('releases_list');
    $sudo = get('cleanup_use_sudo') ? 'sudo' : '';
    $cleanupPaths = get('cleanup_paths');

    foreach ($releases as $release) {
        if ($release === $currentRelease) {
            continue;
        }

        within("{{deploy_path}}/releases/$release", function () use ($sudo, $release, $cleanupPaths): void {
            foreach ($cleanupPaths as $cleanupPath) {
                if (test("[ -e $cleanupPath ]")) {
                    run("$sudo rm -rf $cleanupPath");
                } else {
                    writeln(
                        "Skipped '{{deploy_path}}/releases/$release/$cleanupPath' because the path does not exist.",
                    );
                }
            }
        });
    }
});
