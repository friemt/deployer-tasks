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
use function Deployer\parse;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;
use function Deployer\writeln;

set('template_history', '{{deploy_path}}/.dep/template_log');
set(
    'template_target_lookup',
    fn(): array => [currentHost()->getAlias(), currentHost()->getHostname(), currentHost()->getLabels()['stage']],
);

task('template:check', function (): void {
    $configured = get('template:local:configured');
    $remote = get('template:remote:configured');
    $all = array_merge_recursive($configured, $remote);

    $table = [];

    foreach ($all as $destination => $template) {
        $source = $template['source'] ?? null;
        $status = $template['status'] ?? null;
        $time = $template['time'] ?? null;

        if (null !== $source && null !== $status) {
            $info = 'success' === $status ? '<info>OK</info>' : '<error>ERR</error>';
            $table[] = [$info, $time, $source, $destination];

            continue;
        }

        if (null === $status) {
            $table[] = ['<comment>NEW</comment>', null, $source, $destination];

            continue;
        }

        $table[] = ['<error>OLD</error>', $time, null, $destination];
    }

    (new Table(output()))
        ->setHeaderTitle(currentHost()->getAlias())
        ->setHeaders(['Status', 'Time', 'Source', 'Destination'])
        ->setRows($table)
        ->render();
});

task('template:sync', function (): void {
    $configured = get('template:local:configured');
    $remote = get('template:remote:configured');
    $all = array_merge_recursive($configured, $remote);

    writeln('Syncing templates...');

    $meta = [];

    foreach ($all as $destination => $template) {
        $meta[$destination] = [
            'time' => $template['time'] ?? null,
            'status' => $template['status'] ?? null,
        ];

        $source = $template['source'] ?? null;

        if (null === $source) {
            writeln(sprintf('%1$s: <comment>can be deleted</comment>', $destination));

            continue;
        }

        $meta[$destination]['status'] = 'error';
        $content = file_get_contents($source);

        if (false === $content) {
            writeln(sprintf('%1$s: <error>failed</error>', $destination));

            continue;
        }

        $tmp = tempnam(dirname($source), 'dep');
        $result = file_put_contents($tmp, parse($content));

        if (false === $result) {
            writeln(sprintf('%1$s: <error>failed</error>', $destination));

            continue;
        }

        try {
            upload($tmp, $destination, ['flags' => '-azcP']);

            $time = (new DateTime('now', new DateTimeZone('UTC')))->format(DateTimeInterface::RFC3339);

            $meta[$destination]['status'] = 'success';
            $meta[$destination]['time'] = $time;

            writeln(sprintf('%1$s: <info>succeeded</info>', $destination));
        } catch (Throwable $throwable) {
            writeln(sprintf('%1$s: <error>failed</error>', $destination));
            writeln(sprintf('%1$s: %2$s', $destination, $throwable->getMessage()));
        } finally {
            unlink($tmp);
        }
    }

    run(sprintf('echo \'%1$s\' >> {{template_history}}', json_encode($meta, JSON_THROW_ON_ERROR)));
});

set('template:local:configured', function (): array {
    writeln('Read local config...');

    $jobs = get('template_jobs', []) ?? [];
    $destinations = get('template_target_lookup');

    $filtered = array_filter($jobs, function (array $job) use ($destinations): bool {
        if (false === is_string($job['source']) || false === is_string($job['dest']) || '' === trim($job['dest'])) {
            return false;
        }

        $source = parse($job['source']);

        if (false === is_file($source) || false === is_readable($source)) {
            return false;
        }

        $targets = $job['targets'] ?? [];

        if (count($targets) <= 0) {
            return true;
        }

        return count(array_intersect($targets, $destinations)) > 0;
    });

    writeln(sprintf('<info>Local templates:</info> %1$d', count($filtered)));

    $templates = [];

    foreach ($filtered as $template) {
        $destination = parse($template['dest']);

        $templates[$destination] = [
            'source' => parse($template['source']),
        ];
    }

    return $templates;
});

set('template:remote:configured', function (): array {
    writeln('Read remote config...');

    if (false === test('[ -f {{template_history}} ]')) {
        return [];
    }

    $history = run('tail -n 1 {{template_history}}');
    $entries = json_decode(trim($history), true, 512, JSON_THROW_ON_ERROR);

    writeln(sprintf('<info>Remote templates:</info> %1$d', count($entries)));

    $templates = [];

    foreach ($entries as $destination => $template) {
        $templates[$destination] = [
            'status' => $template['status'],
            'time' => $template['time'],
        ];
    }

    return $templates;
});
