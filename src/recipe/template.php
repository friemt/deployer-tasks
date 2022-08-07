<?php declare(strict_types=1);

use function Deployer\after;

require_once 'task/template.php';

after('deploy:symlink', 'template:sync');
