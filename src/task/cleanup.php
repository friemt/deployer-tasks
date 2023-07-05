<?php declare(strict_types=1);

namespace Friemt\Deployer\Tasks;

use Deployer\Exception\RunException;
use Exception;
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

        $releasePath = sprintf('{{deploy_path}}/releases/%1$s', $release);

        if (false === test(sprintf('[ -e %1$s ]', $releasePath))) {
            writeln(sprintf('Skipped "<comment>%1$s</comment>". The path does not exist.', $releasePath));
            continue;
        }

        foreach ($cleanupPaths as $cleanupPath) {
            $absoluteRemovePath = sprintf('%1$s/%2$s', $releasePath, $cleanupPath);

            if (false === test(sprintf('[ -e %1$s ]', $cleanupPath))) {
                writeln(sprintf('Skipped "<comment>%1$s</comment>". The path does not exist.', $absoluteRemovePath));

                continue;
            }

            writeln(sprintf('Removing "%1$s"', $absoluteRemovePath));

            try {
                run(sprintf('%1$s rm -rf %2$s', $sudo, $cleanupPath));
                writeln(sprintf('Removed "<info>%1$s</info>".', $absoluteRemovePath));
            } catch (RunException $exception) {
                writeln(sprintf('Failed to remove "<comment>%1$s</comment>". %2$s', $absoluteRemovePath, $exception->getErrorOutput()));
            } catch (Exception $exception) {
                writeln(sprintf('Failed to remove "<comment>%1$s</comment>".', $absoluteRemovePath));
            }
        }
    }
});
