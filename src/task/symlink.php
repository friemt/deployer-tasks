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

set('symlink_config', []);

task('symlink:create', function (): void {
    $symlinks = get('symlink_config');

    if (!is_iterable($symlinks)) {
        writeln('<error>Configuration is not an array.</error>');

        return;
    }

    foreach ($symlinks as $destination => $source) {
        if (!is_string($destination) || !is_string($source)) {
            writeln('<error>Skipping misconfigured symlink.</error>');

            continue;
        }

        if (!test(sprintf('[ -e "%1$s" ]', $source))) {
            writeln(sprintf('Skipped "<comment>%1$s</comment>". The path does not exist.', $source));

            continue;
        }

        if (test(sprintf('[ -h "%1$s" ]', $destination))) {
            writeln(sprintf('Skipped "<comment>%1$s</comment>". The symlink already exists.', $destination));

            continue;
        }

        writeln(sprintf('Creating symlink from "%1$s" to "%2$s".', $source, $destination));

        try {
            run(sprintf('{{bin/symlink}} "%1$s" "%2$s"', $source, $destination));
            writeln(sprintf('Created "<info>%1$s</info>".', $destination));
        } catch (RunException $exception) {
            writeln(sprintf('Failed to create "<comment>%1$s</comment>". %2$s', $destination, $exception->getErrorOutput()));
        } catch (Exception $exception) {
            writeln(sprintf('Failed to create "<comment>%1$s</comment>".', $destination));
        }
    }
});
