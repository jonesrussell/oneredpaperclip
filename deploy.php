<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:jonesrussell/oneredpaperclip.git');
set('keep_releases', 5);

// Timestamp-based release names avoid "release already exists" after a failed deploy.
set('release_name', function (): string {
    return date('YmdHis');
});

add('shared_files', [
    '.env',
    'database/database.sqlite',
]);
add('shared_dirs', []);
add('writable_dirs', [
    'storage',
    'bootstrap/cache',
    'database',
]);

// PHP-FPM runs as www-data (in deployer group); ensure group can write.
// Storage is shared (symlink); some files may be owned by www-data, so chmod can fail — ignore.
task('deploy:writable_web', function (): void {
    run('chgrp -R deployer {{release_path}}/storage {{release_path}}/bootstrap/cache || true');
    run('chmod -R g+rwX {{release_path}}/storage {{release_path}}/bootstrap/cache || true');
});
after('deploy:writable', 'deploy:writable_web');

// Hosts

host('coforge.xyz')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/oneredpaperclip');

// Tasks

task('deploy:ensure_shared_database', function (): void {
    run('mkdir -p {{deploy_path}}/shared/database');
    run('touch {{deploy_path}}/shared/database/database.sqlite');
});
after('deploy:shared', 'deploy:ensure_shared_database');

task('deploy:build_assets', function (): void {
    run('bash -lc "source ~/.nvm/nvm.sh 2>/dev/null; cd {{release_path}} && npm ci && npm run build:ssr"');
});
after('deploy:vendors', 'deploy:build_assets');

task('deploy:wayfinder', function (): void {
    cd('{{release_path}}');
    run('{{bin/php}} artisan wayfinder:generate --with-form');
});
before('deploy:build_assets', 'deploy:wayfinder');

task('deploy:install_services', function (): void {
    $serviceDir = '~/.config/systemd/user';
    run("mkdir -p $serviceDir");
    run("cp {{release_path}}/deploy/systemd-user/*.service $serviceDir/");
    run('systemctl --user daemon-reload || true');
    run('systemctl --user enable oneredpaperclip-horizon.service oneredpaperclip-inertia-ssr.service oneredpaperclip-schedule-work.service || true');
    // Optional: enable NorthCloud article feed subscriber when using Redis pipeline:
    // run('systemctl --user enable oneredpaperclip-northcloud-subscribe.service || true');
});
before('deploy:symlink', 'deploy:install_services');

task('deploy:copy_caddyfile', function (): void {
    run('cp {{release_path}}/Caddyfile {{deploy_path}}/Caddyfile');
});
after('deploy:symlink', 'deploy:copy_caddyfile');

task('deploy:reload_caddy', function (): void {
    run('sudo systemctl reload caddy || true');
});
after('deploy:copy_caddyfile', 'deploy:reload_caddy');

task('deploy:restart_services', function (): void {
    run('cd {{release_path}} && {{bin/php}} artisan horizon:terminate || true');
    run('cd {{release_path}} && {{bin/php}} artisan inertia:stop-ssr || true');
    run('systemctl --user restart oneredpaperclip-horizon.service oneredpaperclip-inertia-ssr.service oneredpaperclip-schedule-work.service oneredpaperclip-northcloud-subscribe.service 2>/dev/null || true');
});
after('deploy:symlink', 'deploy:restart_services');

task('deploy:reload_php_fpm', function (): void {
    run('sudo systemctl restart php8.4-fpm || true');
});
after('deploy:restart_services', 'deploy:reload_php_fpm');

// Hooks

after('deploy:failed', 'deploy:unlock');
before('deploy:symlink', 'artisan:migrate');

// Disable view caching — Inertia renders views client-side and artisan:view:cache
// tries to connect to the SSR server which isn't running during deploy.
task('artisan:view:cache', function (): void {});
