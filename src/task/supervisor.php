<?php declare(strict_types=1);

namespace Friemt\Deployer\Tasks;

use Deployer\Exception\RunException;
use Exception;
use function Deployer\get;
use function Deployer\info;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\warning;
use function Deployer\which;
use function Deployer\writeln;

set('bin/supervisor', static fn(): string => which('supervisorctl'));
set('supervisor_groups', []);
set('supervisor_use_sudo', false);

task('supervisor:stop', static function (): void {
    $groups = get('supervisor_groups');

    if (!is_iterable($groups)) {
        warning('"supervisor_groups" is not configured correctly!');

        return;
    }

    $sudo = get('supervisor_use_sudo') ? 'sudo' : '';

    foreach ($groups as $key => $group) {
        if (is_callable($group)) {
            $group = $group();
        }

        if (!is_string($group)) {
            info(sprintf('Group "<comment>%1$s</comment>" is not configured correctly.', $key));

            continue;
        }

        writeln(sprintf('Stopping "<comment>%1$s</comment>" ...', $group));

        try {
            run(sprintf('%1$s {{bin/supervisor}} stop "%2$s:"', $sudo, $group));
            writeln(sprintf('... "<info>%1$s</info>" stopped.', $group));
        } catch (RunException $exception) {
            warning(sprintf('... failed to stop "<info>%1$s</info>".', $group));
            warning($exception->getErrorOutput());
        } catch (Exception $exception) {
            warning(sprintf('... failed to stop "<info>%1$s</info>".', $group));
        }
    }
});

task('supervisor:start', static function (): void {
    $groups = get('supervisor_groups');

    if (!is_iterable($groups)) {
        warning('"supervisor_groups" is not configured correctly!');

        return;
    }

    $sudo = get('supervisor_use_sudo') ? 'sudo' : '';

    foreach ($groups as $key => $group) {
        if (is_callable($group)) {
            $group = $group();
        }

        if (!is_string($group)) {
            info(sprintf('Group "<comment>%1$s</comment>" is not configured correctly.', $key));

            continue;
        }

        writeln(sprintf('Stopping "<comment>%1$s</comment>" ...', $group));

        try {
            run(sprintf('%1$s {{bin/supervisor}} update "%2$s"', $sudo, $group));
            run(sprintf('%1$s {{bin/supervisor}} start "%2$s:"', $sudo, $group));
            writeln(sprintf('... "<info>%1$s</info>" started.', $group));
        } catch (RunException $exception) {
            warning(sprintf('... failed to start "<info>%1$s</info>".', $group));
            warning($exception->getErrorOutput());
        } catch (Exception $exception) {
            warning(sprintf('... failed to start "<info>%1$s</info>".', $group));
        }
    }
});
