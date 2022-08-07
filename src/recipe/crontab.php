<?php declare(strict_types=1);

use function Deployer\after;

require_once 'task/crontab.php';

after('deploy:symlink', 'crontab:sync');
