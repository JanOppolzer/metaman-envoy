@include('vendor/autoload.php')

@setup
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__, 'config');

    try {
        $dotenv->load();
        $dotenv->required([
            'TARGET_SERVER', 'TARGET_USER', 'TARGET_DIR',
            'REPOSITORY',
            'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL',
            'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
            'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'MAIL_REPLYTO_ADDRESS', 'MAIL_REPLYTO_NAME', 'MAIL_ADMIN_ADDRESS',
            'GIT_SSH_KEY', 'GIT_REMOTE', 'GIT_REMOTE_BRANCH', 'GIT_LOCAL', 'GIT_USER_NAME', 'GIT_USER_EMAIL', 'GIT_EDUGAIN_CFG', 'GIT_EDUGAIN_TAG',
            'METADATA_BASE_URL',
        ])->notEmpty();
    }
    catch(Exception $e)
    {
        echo "Something went wrong:\n\n";
        echo "{$e->getMessage()} \n\n";
        exit;
    }

    // Target server, user and directory
    $server                = $_ENV['TARGET_SERVER'];
    $user                  = $_ENV['TARGET_USER'];
    $dir                   = $_ENV['TARGET_DIR'];

    // Source code repository
    $repository            = $_ENV['REPOSITORY'];

    // Source code repository branch
    $branch                = $branch ?? 'main';

    // Application name, environment, debug and URL
    $app_name              = $_ENV['APP_NAME'];
    $app_env               = $_ENV['APP_ENV'];
    $app_debug             = $_ENV['APP_DEBUG'];
    $app_url               = $_ENV['APP_URL'];

    // Database
    $db_host               = $_ENV['DB_HOST'];
    $db_database           = $_ENV['DB_DATABASE'];
    $db_username           = $_ENV['DB_USERNAME'];
    $db_password           = $_ENV['DB_PASSWORD'];

    // Mail
    $mail_mailer           = $_ENV['MAIL_MAILER'];
    $mail_host             = $_ENV['MAIL_HOST'];
    $mail_port             = $_ENV['MAIL_PORT'];
    $mail_username         = $_ENV['MAIL_USERNAME'];
    $mail_password         = $_ENV['MAIL_PASSWORD'];
    $mail_encryption       = $_ENV['MAIL_ENCRYPTION'];
    $mail_from_address     = $_ENV['MAIL_FROM_ADDRESS'];
    $mail_from_name        = $_ENV['MAIL_FROM_NAME'];
    $mail_replyto_address  = $_ENV['MAIL_REPLYTO_ADDRESS'];
    $mail_replyto_name     = $_ENV['MAIL_REPLYTO_NAME'];
    $mail_admin_address    = $_ENV['MAIL_ADMIN_ADDRESS'];

    // Git repository with federation metadata
    $git_ssh_key           = $_ENV['GIT_SSH_KEY'];
    $git_remote            = $_ENV['GIT_REMOTE'];
    $git_remote_branch     = $_ENV['GIT_REMOTE_BRANCH'];
    $git_local             = $_ENV['GIT_LOCAL'];
    $git_user_name         = $_ENV['GIT_USER_NAME'];
    $git_user_email        = $_ENV['GIT_USER_EMAIL'];
    $git_edugain_cfg       = $_ENV['GIT_EDUGAIN_CFG'];
    $git_edugain_tag       = $_ENV['GIT_EDUGAIN_TAG'];

    // Signed metadata feed URL
    $metadata_base_url     = $_ENV['METADATA_BASE_URL'];

    // Slack
    $slack_hook            = $_ENV['LOG_SLACK_WEBHOOK_URL'] ?? null;
    $slack_channel         = $_ENV['LOG_SLACK_CHANNEL'] ?? null;

    $destination = (new DateTime)->format('YmdHis');
    $symlink = 'current';
@endsetup

@servers(['web' => "$user@$server"])

@task('deploy', ['confirm' => true])
    echo "=> Install {{ $app_name }} into ~/{{ $dir }}/ at {{ $user }}"@"{{ $server }}..."

    echo "Check ~/{{ $dir }}/"
    if [ ! -d {{ $dir }} ]; then
        mkdir -p {{ $dir }}
    fi

    cd {{ $dir }}

    echo "Clone '{{ $branch }}' branch of {{ $repository }} into ~/{{ $dir }}/{{ $destination }}/"
    git clone {{ $repository }} --branch={{ $branch }} --depth=1 -q ~/{{ $dir }}/{{ $destination }}

    echo "Prepare ~/{{ $dir }}/.env"
    if [ ! -f .env ]; then
        cp {{ $destination }}/.env.example .env
    fi

    echo "Update ~/{{ $dir }}/.env"
    cp .env .env-{{ $destination }}.bak
    sed -i "s%APP_NAME=.*%APP_NAME={{ $app_name }}%; \
    s%APP_ENV=.*%APP_ENV={{ $app_env }}%; \
    s%APP_DEBUG=.*%APP_DEBUG={{ $app_debug }}%; \
    s%APP_URL=.*%APP_URL={{ $app_url }}%; \
    s%DB_HOST=.*%DB_HOST={{ $db_host }}%; \
    s%DB_DATABASE=.*%DB_DATABASE={{ $db_database }}%; \
    s%DB_USERNAME=.*%DB_USERNAME={{ $db_username }}%; \
    s%DB_PASSWORD=.*%DB_PASSWORD={{ $db_password }}%; \
    s%MAIL_MAILER=.*%MAIL_MAILER={{ $mail_mailer }}%; \
    s%MAIL_HOST=.*%MAIL_HOST={{ $mail_host }}%; \
    s%MAIL_PORT=.*%MAIL_PORT={{ $mail_port }}%; \
    s%MAIL_USERNAME=.*%MAIL_USERNAME={{ $mail_username }}%; \
    s%MAIL_PASSWORD=.*%MAIL_PASSWORD={{ $mail_password }}%; \
    s%MAIL_ENCRYPTION=.*%MAIL_ENCRYPTION={{ $mail_encryption }}%; \
    s%MAIL_FROM_ADDRESS=.*%MAIL_FROM_ADDRESS={{ $mail_from_address }}%; \
    s%MAIL_FROM_NAME=.*%MAIL_FROM_NAME={{ $mail_from_name }}%; \
    s%MAIL_REPLYTO_ADDRESS=.*%MAIL_REPLYTO_ADDRESS={{ $mail_replyto_address }}%; \
    s%MAIL_REPLYTO_NAME=.*%MAIL_REPLYTO_NAME={{ $mail_replyto_name }}%; \
    s%MAIL_ADMIN_ADDRESS=.*%MAIL_ADMIN_ADDRESS={{ $mail_admin_address }}%; \
    s%GIT_SSH_KEY=.*%GIT_SSH_KEY={{ $git_ssh_key }}%; \
    s%GIT_REMOTE=.*%GIT_REMOTE={{ $git_remote }}%; \
    s%GIT_REMOTE_BRANCH=.*%GIT_REMOTE_BRANCH={{ $git_remote_branch }}%; \
    s%GIT_LOCAL=.*%GIT_LOCAL={{ $git_local }}%; \
    s%GIT_USER_NAME=.*%GIT_USER_NAME={{ $git_user_name }}%; \
    s%GIT_USER_EMAIL=.*%GIT_USER_EMAIL={{ $git_user_email }}%; \
    s%GIT_EDUGAIN_CFG=.*%GIT_EDUGAIN_CFG={{ $git_edugain_cfg }}%; \
    s%GIT_EDUGAIN_TAG=.*%GIT_EDUGAIN_TAG={{ $git_edugain_tag }}%; \
    s%METADATA_BASE_URL=.*%METADATA_BASE_URL={{ $metadata_base_url }}%; \
    s%LOG_SLACK_WEBHOOK_URL=.*%LOG_SLACK_WEBHOOK_URL={{ $slack_hook }}%" .env

    echo "Symlink ~/{{ $dir }}/.env"
    ln -s ../.env ~/{{ $dir }}/{{ $destination }}/.env

    echo "Check ~/{{ $dir }}/storage/"
        if [ ! -d storage ]; then
            mv {{ $destination }}/storage .
        else
            rm -rf {{ $destination }}/storage
        fi

    if [ ! -d storage/git ]; then
        echo "Fix permissions to ~/{{ $dir }}/storage/"
        setfacl -Rm g:www-data:rwx,d:g:www-data:rwx storage
    fi

    echo "Symlink ~/{{ $dir }}/storage/"
        ln -s ../storage {{ $destination }}/storage

    echo "Unlink ~/{{ $dir }}{{ $symlink }}"
        if [ -h {{ $symlink }} ]; then
            rm {{ $symlink }}
        fi

    echo "Symlink ~/{{ $dir }}/{{ $destination }} to ~/{{ $dir }}/{{ $symlink }}"
        ln -s {{ $destination }} {{ $symlink }}

    echo "Install composer dependencies"
        cd current
        composer install -q --no-dev --optimize-autoloader --no-ansi --no-interaction --no-progress --prefer-dist
        cd ..

    echo "Generate key"
        if [ `grep '^APP_KEY=' .env | grep 'base64:' | wc -l` -eq 0 ]; then
            cd current
            php artisan key:generate -q --no-ansi --no-interaction
            cd ..
        fi

    cd {{ $destination }}

    echo "Migrate database tables"
        php artisan migrate --force -q --no-ansi --no-interaction

    echo "Optimize"
        php artisan optimize:clear -q --no-ansi --no-interaction

    echo "Cache config"
        php artisan config:cache -q --no-ansi --no-interaction

    echo "Cache routes"
        php artisan route:cache -q --no-ansi --no-interaction

    echo "Cache views"
        php artisan view:cache -q --no-ansi --no-interaction

    echo "Restart queue"
        php artisan queue:restart -q --no-ansi --no-interaction

    echo "Reload PHP-FPM"
        sudo systemctl reload php7.4-fpm
@endtask

@task('cleanup')
    cd {{ $dir }}
    find . -maxdepth 1 -name "20*" | sort | head -n -3 | xargs rm -rf
    echo "Cleaned up all but the last 3 deployments."
@endtask

@task('rollback')
    cd {{ $dir }}
    ln -sfn `find . -maxdepth 1 -name "20*" | sort | tail -n2 | head -n1` {{ $symlink }}
    echo "Rolled back to `find . -maxdepth 1 -name "20*" | sort | tail -n2 | head -n1`"
@endtask

@finished
    @slack($slack_hook, $slack_channel, "$app_name deployed to $server.")
@endfinished
