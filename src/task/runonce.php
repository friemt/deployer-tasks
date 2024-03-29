<?php declare(strict_types=1);

namespace Friemt\Deployer\Tasks;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\Console\Helper\Table;
use Throwable;
use function Deployer\currentHost;
use function Deployer\get;
use function Deployer\output;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\writeln;

set('runonce_history', '{{deploy_path}}/.dep/runonce_log');
set(
    'runonce_target_lookup',
    fn(): array => array_filter([
        currentHost()->getAlias(),
        currentHost()->getHostname(),
        null !== currentHost()->getLabels() ? currentHost()->getLabels()['stage'] : null,
    ]),
);

task('runonce:check', function (): void {
    $configured = get('runonce:local:configured');
    $remote = get('runonce:remote:history');
    $table = [];

    krsort($configured);

    foreach ($configured as $key => $job) {
        if (false === array_key_exists($key, $remote)) {
            $table[] = [$key, '<comment>NEW</comment>', null, $job['command'] ?? null, null];

            continue;
        }

        $runs = $remote[$key];

        krsort($runs);

        foreach ($runs as $run) {
            $table[] = [
                $key,
                getRunStatus($job, $run),
                $run['time'] ?? null,
                $job['command'] ?? null,
                $run['output'] ?? null,
            ];
        }
    }

    (new Table(output()))
        ->setHeaderTitle(currentHost()->getAlias())
        ->setHeaders(['Key', 'Status', 'Time', 'Command', 'Output'])
        ->setRows($table)
        ->render();
});

task('runonce:run', function (): void {
    $configured = get('runonce:local:configured');
    $remote = get('runonce:remote:history');

    ksort($configured);

    foreach ($configured as $key => $job) {
        if (array_key_exists($key, $remote)) {
            $succeeded = array_filter($remote[$key], static fn(array $run) => 'success' === $run['status']);

            if (count($succeeded) > 0) {
                writeln(sprintf('%1$s: already succeeded', $key));

                continue;
            }

            if (false === $job['retry']) {
                writeln(sprintf('%1$s: already failed, for good', $key));

                continue;
            }

            writeln(sprintf('%1$s: already failed, retry ...', $key));
        }

        $meta = [
            'key' => $key,
            'output' => null,
            'status' => null,
            'time' => (new DateTime('now', new DateTimeZone('UTC')))->format(DateTimeInterface::RFC3339),
        ];

        try {
            $meta['output'] = run($job['command']);
            $meta['status'] = 'success';

            writeln(sprintf('%1$s: <info>succeeded</info>', $key));
        } catch (Throwable $throwable) {
            $meta['output'] = $throwable->getMessage();
            $meta['status'] = 'error';

            writeln(sprintf('%1$s: <error>failed</error>', $key));
        }

        if ('' !== trim($meta['output'])) {
            writeln($meta['output']);
        }

        if (false === $job['verbose']) {
            $meta['output'] = substr($meta['output'], 0, 64);
        }

        run(sprintf('echo \'%1$s\' >> {{runonce_history}}', json_encode($meta, JSON_THROW_ON_ERROR)));
    }
});

set('runonce:local:configured', function (): array {
    writeln('Read local config...');

    $jobs = get('runonce_jobs', []) ?? [];
    $destinations = get('runonce_target_lookup');

    $filtered = array_filter($jobs, static function (array $job) use ($destinations): bool {
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

    writeln(sprintf('<info>Local jobs:</info> %1$d', count($filtered)));

    return array_map(
        static fn(array $job): array => [
            'command' => trim($job['command']),
            'verbose' => $job['verbose'] ?? false,
            'retry' => $job['retry'] ?? false,
        ],
        $filtered,
    );
});

set('runonce:remote:history', function (): array {
    writeln('Read remote history...');

    if (false === test('[ -f {{runonce_history}} ]')) {
        return [];
    }

    $history = run('cat {{runonce_history}}');
    $lines = array_filter(array_map('trim', explode("\n", $history)));
    $entries = [];

    foreach ($lines as $line) {
        $data = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

        $entries[$data['key']][$data['time']] = $data;
    }

    writeln(sprintf('<info>Remote jobs:</info> %1$d', count($entries)));
    writeln(sprintf('<info>Remote executions:</info> %1$d', count($lines)));

    return $entries;
});

function getRunStatus(array $job, array $run): string
{
    if ('success' === $run['status']) {
        return '<info>SUCCESS</info>';
    }

    if ($job['retry']) {
        return '<comment>RETRY</comment>';
    }

    return '<error>FAILURE</error>';
}
