<?php declare(strict_types=1);

use function Deployer\after;
use function Deployer\before;

require_once __DIR__ . '/../task/supervisor.php';

before('deploy:publish', 'supervisor:stop');
after('deploy:publish', 'supervisor:start');
