<?php declare(strict_types=1);

use function Deployer\after;

require_once 'task/runonce.php';

after('deploy:symlink', 'runonce:run');
