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

set('symlink_config', []);

task('symlink:create', static function (): void {
    $symlinks = get('symlink_config');

    if (!is_iterable($symlinks)) {
        warning('"symlink_config" is not configured correctly!');

        return;
    }

    foreach ($symlinks as $destination => $source) {
        if (!is_string($destination) || !is_string($source)) {
            info('Symlink is not configured correctly.');

            continue;
        }

        if (!test(sprintf('[ -e "%1$s" ]', $source))) {
            info(sprintf('Path "<comment>%1$s</comment>" does not exist.', $source));

            continue;
        }

        if (test(sprintf('[ -h "%1$s" ]', $destination))) {
            info(sprintf('Symlink "<comment>%1$s</comment>" already exists.', $destination));

            continue;
        }

        writeln(sprintf('Creating "<comment>%1$s</comment>" => "<comment>%2$s</comment>" ...', $source, $destination));

        try {
            run(sprintf('{{bin/symlink}} "%1$s" "%2$s"', $source, $destination));
            writeln(sprintf('... "<info>%1$s</info>" created.', $destination));
        } catch (RunException $exception) {
            warning(sprintf('... failed to create "<info>%1$s</info>".', $destination));
            warning($exception->getErrorOutput());
        } catch (Exception $exception) {
            warning(sprintf('... failed to create "<info>%1$s</info>".', $destination));
        }
    }
});
