<?php
namespace Deployer;

require 'recipe/symfony.php';

// Config
set('repository', 'https://github.com/ksn135/hlk.git');
set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader');

add('shared_files', [
    '.env.local',
    '.env.local.php',
]);

add('shared_dirs', [
    'var/log',
    'public/files',
]);

add('writable_dirs', [
    'var',
    'var/cache',
    'var/log',
    'var/sessions',
    'public/files',
]);

set('cleanup_use_sudo', true);

// Hosts
host('prod')
    ->setHostname('vis')
    ->set('deploy_path', '/var/www/hlk')
    ->set('branch', 'main')
    ->set('redis_db', 5);

desc('Flush REDIS cache');
task('redis:flush:all', static function () {
    $db = (int) get('redis_db');
    run("sudo redis-cli -n $db flushdb");
});

desc('Reset apache');
task('apachectl:graceful', static function () {
    run('sudo apachectl -k graceful');
});

desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    // The user must have rights for restart service
    // /etc/sudoers: username ALL=NOPASSWD:/bin/systemctl restart php-fpm.service
    run('sudo systemctl restart php8.1-fpm.service');
});

desc('Compile Asset Mapper assets into public/assets');
task('deploy:asset-map:compile', function () {
    run('cd {{release_or_current_path}} && {{bin/console}} importmap:install {{console_options}}');
    run('cd {{release_or_current_path}} && {{bin/console}} asset-map:compile {{console_options}}');
});

desc('Reset services');
task('reset:services', [
    'redis:flush:all',
    'apachectl:graceful',
    'php-fpm:restart',
]);

// Hooks
after('deploy:failed', 'deploy:unlock');
after('deploy:vendors', 'deploy:asset-map:compile');
after('deploy:symlink', 'reset:services');
