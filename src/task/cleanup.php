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
    $currentRelease = basename(within('{{release_or_current_path}}', fn() => run('pwd -P')));
    $releases = get('releases_list');
    $sudo = get('cleanup_use_sudo') ? 'sudo' : '';
    $cleanupPaths = get('cleanup_paths', []) ?? [];

    foreach ($releases as $release) {
        if ($release === $currentRelease) {
            continue;
        }

        $withinPath = sprintf('{{deploy_path}}/releases/%s', $release);
        within($withinPath, function () use ($sudo, $release, $cleanupPaths): void {
            foreach ($cleanupPaths as $cleanupPath) {
                writeln('Removing "%1$s"', $cleanupPath);
                if (false === test(sprintf('[ -e %s ]', $cleanupPath))) {
                    writeln(
                        sprintf(
                            '<comment>Skipped "{{deploy_path}}/releases/%s/%s" because the path does not exist.</comment>',
                            $release,
                            $cleanupPath,
                        ),
                    );
                    continue;
                }

                run(sprintf('%s rm -rf %s', $sudo, $cleanupPath));
                writeln('<info>Removed "%1$s"</info>', $cleanupPath);
            }
        });
    }
});
