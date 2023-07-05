<?php declare(strict_types=1);

namespace Friemt\Deployer\Tasks;

use Deployer\Exception\RunException;
use Exception;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\writeln;

set('cleanup_paths', ['var/cache']);

task('cleanup:paths', function (): void {
    $releases = get('releases_list');
    $keep = get('keep_releases', 0)
    $sudo = get('cleanup_use_sudo') ? 'sudo' : '';
    $cleanupPaths = get('cleanup_paths', []) ?? [];

    if ($keep <= 0 || count($cleanupPaths) <= 0) {
        writeln('Nothing to remove.');

        return;
    }

    foreach (array_slice($releases, 1, $keep - 1) as $release) {
        $releasePath = sprintf('{{deploy_path}}/releases/%1$s', $release);

        if (false === test(sprintf('[ -e %1$s ]', $releasePath))) {
            writeln(sprintf('Skipped "<comment>%1$s</comment>". The path does not exist.', $releasePath));

            continue;
        }

        foreach ($cleanupPaths as $cleanupPath) {
            $absolutePath = sprintf('%1$s/%2$s', $releasePath, $cleanupPath);

            if (false === test(sprintf('[ -e %1$s ]', $cleanupPath))) {
                writeln(sprintf('Skipped "<comment>%1$s</comment>". The path does not exist.', $absolutePath));

                continue;
            }

            writeln(sprintf('Removing "%1$s"', $absolutePath));

            try {
                run(sprintf('%1$s rm -rf %2$s', $sudo, $absolutePath));
                writeln(sprintf('Removed "<info>%1$s</info>".', $absolutePath));
            } catch (RunException $exception) {
                writeln(sprintf('Failed to remove "<comment>%1$s</comment>". %2$s', $absolutePath, $exception->getErrorOutput()));
            } catch (Exception $exception) {
                writeln(sprintf('Failed to remove "<comment>%1$s</comment>".', $absolutePath));
            }
        }
    }
});
