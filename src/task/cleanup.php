<?php declare(strict_types=1);

namespace Friemt\Deployer\Tasks;

use Deployer\Exception\RunException;
use Exception;
use function Deployer\get;
use function Deployer\info;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\warning;
use function Deployer\writeln;

set('cleanup_paths', ['var/cache']);

task('cleanup:paths', static function (): void {
    $paths = get('cleanup_paths');

    if (!is_iterable($paths)) {
        warning('"cleanup_paths" is not configured correctly!');

        return;
    }

    $keep = get('keep_releases', 0);

    if ($keep <= 0) {
        warning('"keep_releases" does not allow old releases, nothing to remove.');

        return;
    }

    $releases = get('releases_list');
    $sudo = get('cleanup_use_sudo') ? 'sudo' : '';

    foreach (array_slice($releases, 1, $keep - 1) as $release) {
        $releasePath = sprintf('{{deploy_path}}/releases/%1$s', $release);

        if (!test(sprintf('[ -e "%1$s" ]', $releasePath))) {
            info(sprintf('Path "<comment>%1$s</comment>" does not exist.', $releasePath));

            continue;
        }

        foreach ($paths as $path) {
            $absolutePath = sprintf('%1$s/%2$s', $releasePath, $path);

            if (!test(sprintf('[ -e "%1$s" ]', $absolutePath))) {
                info(sprintf('Path "<comment>%1$s</comment>" does not exist.', $absolutePath));

                continue;
            }

            writeln(sprintf('Removing "<comment>%1$s</comment>" ...', $absolutePath));

            try {
                run(sprintf('%1$s rm -rf "%2$s"', $sudo, $absolutePath));
                writeln(sprintf('... "<info>%1$s</info>" removed.', $absolutePath));
            } catch (RunException $exception) {
                warning(sprintf('... failed to remove "<info>%1$s</info>".', $absolutePath));
                warning($exception->getErrorOutput());
            } catch (Exception $exception) {
                warning(sprintf('... failed to remove "<info>%1$s</info>".', $absolutePath));
            }
        }
    }
});
