<?php declare(strict_types=1);

use function Deployer\after;

require_once __DIR__ . '/../task/template.php';

after('deploy:symlink', 'template:sync');
