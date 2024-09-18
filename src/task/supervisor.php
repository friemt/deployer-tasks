<?php declare(strict_types=1);

namespace Friemt\Deployer\Tasks;

use Deployer\Exception\RunException;
use Exception;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\which;
use function Deployer\writeln;

set('bin/supervisor', fn(): string => which('supervisorctl'));
set('supervisor_groups', []);
set('supervisor_use_sudo', false);

task('supervisor:stop', static function (): void {
    $groups = get('supervisor_groups');
    $sudo = get('supervisor_use_sudo') ? 'sudo' : '';

    if (!is_iterable($groups)) {
        writeln('<error>Supervisor groups is not an array.</error>');

        return;
    }

    foreach ($groups as $group) {
        if (is_callable($group)) {
            $group = $group();
        }

        if (!is_string($group)) {
            writeln('<error>Skipping unknown supervisor group.</error>');

            continue;
        }

        writeln(sprintf('Stopping supervisor group: %1$s.', $group));

        try {
            run(sprintf('%1$s {{bin/supervisor}} stop %2$s:', $sudo, $group));
            writeln(sprintf('Stopped "<info>%1$s</info>".', $group));
        } catch (RunException $exception) {
            writeln(sprintf('Failed to stop "<comment>%1$s</comment>". %2$s', $group, $exception->getErrorOutput()));
        } catch (Exception $exception) {
            writeln(sprintf('Failed to stop "<comment>%1$s</comment>".', $group));
        }
    }
});

task('supervisor:start', static function (): void {
    $groups = get('supervisor_groups');
    $sudo = get('supervisor_use_sudo') ? 'sudo' : '';

    if (!is_iterable($groups)) {
        writeln('<error>Supervisor groups is not an array.</error>');

        return;
    }

    foreach ($groups as $group) {
        if (is_callable($group)) {
            $group = $group();
        }

        if (!is_string($group)) {
            writeln('<error>Skipping unknown supervisor group.</error>');

            continue;
        }

        writeln(sprintf('Starting supervisor group: %1$s.', $group));

        try {
            run(sprintf('%1$s {{bin/supervisor}} update %2$s', $sudo, $group));
            run(sprintf('%1$s {{bin/supervisor}} start %2$s:', $sudo, $group));
            writeln(sprintf('Started "<info>%1$s</info>".', $group));
        } catch (RunException $exception) {
            writeln(sprintf('Failed to start "<comment>%1$s</comment>". %2$s', $group, $exception->getErrorOutput()));
        } catch (Exception $exception) {
            writeln(sprintf('Failed to start "<comment>%1$s</comment>".', $group));
        }
    }
});
