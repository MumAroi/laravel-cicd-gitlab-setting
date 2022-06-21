@servers(['web' => 'deployer@192.168.0.1'])

@task('list', ['on' => 'web'])
    ls -l
@endtask

@setup
    $repository = 'git@gitlab.com:{{username}}/{{project}}';
    $releases_dir = '/var/www/html/releases';
    $app_dir = '/var/www/html';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@story('deploy')
    clone_repository
    update_symlinks
    run_composer
    cache_reset
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
    cd {{ $new_release_dir }}
    git reset --hard {{ $commit }}
@endtask

@task('update_symlinks')
    echo 'create storage link to {{ $app_dir }}/storage ...'
    [ -d {{ $app_dir }}/storage ] || mkdir {{ $app_dir }}/storage
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/laravel-ci-cd
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install --prefer-dist --no-scripts -q -o
@endtask

@task('cache_reset')
    echo "Cache reset"
    cd {{ $new_release_dir }}
    php artisan opcache:clear
@endtask


