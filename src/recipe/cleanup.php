<?php declare(strict_types=1);

use function Deployer\after;

require_once __DIR__ . '/../task/cleanup.php';

after('deploy', 'cleanup:paths');
