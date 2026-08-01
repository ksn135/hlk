<?php
namespace Deployer;

require 'recipe/symfony.php';

// Config

set('repository', 'https://github.com/ksn135/hlk.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('host')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/hlk');

// Hooks

after('deploy:failed', 'deploy:unlock');
