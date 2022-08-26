<?php declare(strict_types=1);

namespace Friemt\Deployer\Tasks;

use Symfony\Component\Console\Helper\Table;
use function Deployer\currentHost;
use function Deployer\get;
use function Deployer\output;
use function Deployer\parse;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\which;
use function Deployer\writeln;
use function preg_match;

set('bin/crontab', fn(): string => which('crontab'));
set('crontab_backup', '{{deploy_path}}/.dep/crontab.bak');
set('crontab_marker', '{{application}} {{crontab_stage}}');
set('crontab_stage', fn(): string => currentHost()->getLabels()['stage']);
set('crontab_user', '{{remote_user}}');
set(
    'crontab_target_lookup',
    fn(): array => [currentHost()->getAlias(), currentHost()->getHostname(), currentHost()->getLabels()['stage']],
);

task('crontab:check', function (): void {
    $configured = get('crontab:local:configured');
    $remote = get('crontab:remote:configured');
    $all = array_unique(array_merge($configured, $remote));

    $table = array_map(function (string $tab) use ($configured, $remote): array {
        if (in_array($tab, $configured) && in_array($tab, $remote)) {
            return ['<info>OK</info>', $tab];
        }

        if (in_array($tab, $configured)) {
            return ['<comment>NEW</comment>', $tab];
        }

        return ['<error>OLD</error>', $tab];
    }, $all);

    (new Table(output()))
        ->setHeaderTitle(currentHost()->getAlias())
        ->setHeaders(['Status', 'Tab'])
        ->setRows($table)
        ->render();
});

task('crontab:sync', function (): void {
    $configured = get('crontab:local:configured');
    $content = get('crontab:remote:content');
    $marker = get('crontab_marker');

    writeln('Checking remote...');

    if (false === isCrontabMarked($content, $marker)) {
        writeln('Add missing marker...');

        $content = sprintf('%2$s%1$s###> %3$s ###%1$s###< %3$s ###%1$s', "\n", $content, $marker);
    }

    $crontab = preg_replace(
        sprintf('/###> %1$s ###(.*)###< %1$s ###/s', preg_quote($marker, '/')),
        sprintf('###> %2$s ###%1$s%3$s%1$s###< %2$s ###', "\n", $marker, implode("\n", $configured)),
        $content,
    );

    if (test('{{bin/crontab}} -u {{crontab_user}} -l')) {
        writeln('Backup current tabs...');

        run('{{bin/crontab}} -u {{crontab_user}} -l > {{crontab_backup}}');

        writeln('<info>Backup written to:</info> {{crontab_backup}}');
    }

    writeln('Syncing tabs...');

    run(
        sprintf(
            '{{bin/crontab}} -u {{crontab_user}} - << "%2$s"%1$s%3$s%1$s%2$s%1$s',
            "\n",
            'CRONTABCONTENT',
            $crontab,
        ),
    );

    writeln('<info>Successfully updated tabs!</info>');
});

set('crontab:local:configured', function (): array {
    writeln('Read local config...');

    $jobs = get('crontab_jobs', []) ?? [];
    $destinations = get('crontab_target_lookup');

    $filtered = array_filter($jobs, function (array $job) use ($destinations): bool {
        $command = $job['command'] ?? '';

        if (false === is_string($command) || '' === trim($command)) {
            return false;
        }

        $targets = $job['targets'] ?? [];

        if (count($targets) <= 0) {
            return true;
        }

        return count(array_intersect($targets, $destinations)) > 0;
    });

    writeln(sprintf('<info>Local tabs:</info> %1$d', count($filtered)));

    return array_values(array_map(fn(array $job): string => trim(parse($job['command'])), $filtered));
});

set('crontab:remote:configured', function (): array {
    $content = get('crontab:remote:content');
    $marker = get('crontab_marker');

    writeln('Read remote config...');

    if (false === isCrontabMarked($content, $marker)) {
        writeln('<comment>Marker not found.</comment>');

        return [];
    }

    $result = preg_match(
        sprintf('/%1$s*###> %2$s ###(?<content>.*)###< %2$s ###%1$s*/s', "\n", preg_quote($marker, '/')),
        $content,
        $matches,
        PREG_UNMATCHED_AS_NULL,
    );

    if (false === $result || 0 === $result || null === ($matches['content'] ?? null)) {
        writeln('<error>Marker could not be read!</error>');

        return [];
    }

    $filtered = array_filter(array_map('trim', explode("\n", $matches['content'])));

    writeln(sprintf('<info>Remote tabs:</info> %1$d', count($filtered)));

    return array_values($filtered);
});

set('crontab:remote:content', function (): string {
    writeln('Read remote content...');

    $command = '{{bin/crontab}} -u {{crontab_user}} -l';

    if (false === test(sprintf('%1$s >> /dev/null 2>&1', $command))) {
        return '';
    }

    return run($command);
});

function isCrontabMarked(string $content, string $marker): bool
{
    return str_contains($content, sprintf('###> %1$s ###', $marker));
}
