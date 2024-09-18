<?php declare(strict_types=1);

use function Deployer\before;

require_once 'task/symlink.php';

before('deploy:shared', 'symlink:create');
