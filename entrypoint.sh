#!/bin/bash

echo "Now in entrypoint.sh for Firefly III"
echo "Script:       1.0.21 (2022-02-17)"
echo "User:         '$(whoami)'"
echo "Group:        '$(id -g -n)'"
echo "Working dir:  '$(pwd)'"
echo "Build number:  $(cat /var/www/counter-main.txt)"
echo "Build date:    $(cat /var/www/build-date-main.txt)"
echo "sourcing .env file"

. .env

# https://github.com/docker-library/wordpress/blob/master/docker-entrypoint.sh
# usage: file_env VAR [DEFAULT]
#    ie: file_env 'XYZ_DB_PASSWORD' 'example'
# (will allow for "$XYZ_DB_PASSWORD_FILE" to fill in the value of
#  "$XYZ_DB_PASSWORD" from a file, especially for Docker's secrets feature)
file_env() {
	local var="$1"
	local fileVar="${var}_FILE"
	local def="${2:-}"
	if [ "${!var:-}" ] && [ "${!fileVar:-}" ]; then
		echo >&2 "error: both $var and $fileVar are set (but are exclusive)"
		exit 1
	fi
	local val="$def"
	if [ "${!var:-}" ]; then
		val="${!var}"
	elif [ "${!fileVar:-}" ]; then
		val="$(< "${!fileVar}")"
	fi
	export "$var"="$val"
	unset "$fileVar"
}

# envs that can be appended with _FILE
envs=(
	SITE_OWNER
	APP_KEY
	DB_CONNECTION
	DB_HOST
	DB_PORT
	DB_DATABASE
	DB_USERNAME
	DB_PASSWORD
	PGSQL_SSL_MODE
	PGSQL_SSL_ROOT_CERT
	PGSQL_SSL_CERT
	PGSQL_SSL_KEY
	PGSQL_SSL_CRL_FILE
	REDIS_HOST
	REDIS_PASSWORD
	REDIS_PORT
	COOKIE_DOMAIN
	MAIL_DRIVER
	MAIL_HOST
	MAIL_PORT
	MAIL_FROM
	MAIL_USERNAME
	MAIL_PASSWORD
	MAIL_ENCRYPTION
	MAILGUN_DOMAIN
	MAILGUN_SECRET
	MAILGUN_ENDPOINT
	MANDRILL_SECRET
	SPARKPOST_SECRET
	MAPBOX_API_KEY
	FIXER_API_KEY
	LOGIN_PROVIDER
	WINDOWS_SSO_ENABLED
	WINDOWS_SSO_DISCOVER
	WINDOWS_SSO_KEY
	TRACKER_SITE_ID
	TRACKER_URL
	STATIC_CRON_TOKEN
  PASSPORT_PRIVATE_KEY
  PASSPORT_PUBLIC_KEY
)

echo "Now parsing _FILE variables."
for e in "${envs[@]}"; do
  file_env "$e"
done
echo "done!"

# touch DB file
if [[ $DKR_CHECK_SQLITE != "false" ]]; then
  echo "Touch DB file (if SQLite)..."
  if [[ $DB_CONNECTION == "sqlite" ]]; then
    touch /var/www/html/storage/database/database.sqlite
    echo "Touched!"
  fi
fi

echo "Dump auto load..."
composer dump-autoload
echo "Discover packages..."
php artisan package:discover

echo "Current working dir is '$(pwd)'"

echo "Wait for the database."
if [[ -z "$DB_PORT" ]]; then
  if [[ $DB_CONNECTION == "pgsql" ]]; then
    DB_PORT=5432
  elif [[ $DB_CONNECTION == "mysql" ]]; then
    DB_PORT=3306
  fi
fi
if [[ -n "$DB_PORT" ]]; then
  /usr/local/bin/wait-for-it.sh "${DB_HOST}:${DB_PORT}" -t 60 -- echo "DB is up."
fi

echo "Wait another 5 seconds in case the DB needs to boot."
sleep 5
echo "Done waiting for the DB to boot."

if [[ $DKR_BUILD_LOCALE == "true" ]]; then
  echo "Will build all locales..."
  locale-gen
else
  echo "Will not build the locales..."
fi

echo "Run various artisan commands..."

if [[ $DKR_RUN_MIGRATION == "false" ]]; then
  echo "Will NOT run migration commands."
else
  echo "Running migration commands..."
  php artisan firefly-iii:create-database
  php artisan migrate --seed --no-interaction --force
  php artisan firefly-iii:decrypt-all
fi

echo "Current working dir is '$(pwd)'"

php artisan firefly-iii:fix-pgsql-sequences

# there are 13 upgrade commands
if [[ $DKR_RUN_UPGRADE == "false" ]]; then
  echo 'Will NOT run upgrade commands.'
else
  echo 'Running upgrade commands...'
  php artisan firefly-iii:transaction-identifiers
  php artisan firefly-iii:migrate-to-groups
  php artisan firefly-iii:account-currencies
  php artisan firefly-iii:transfer-currencies
  php artisan firefly-iii:other-currencies
  php artisan firefly-iii:migrate-notes
  php artisan firefly-iii:migrate-attachments
  php artisan firefly-iii:bills-to-rules
  php artisan firefly-iii:bl-currency
  php artisan firefly-iii:cc-liabilities
  php artisan firefly-iii:back-to-journals
  php artisan firefly-iii:rename-account-meta
  php artisan firefly-iii:migrate-recurrence-meta
  php artisan firefly-iii:migrate-tag-locations
  php artisan firefly-iii:migrate-recurrence-type
  php artisan firefly-iii:upgrade-liabilities
  php artisan firefly-iii:liabilities-600
fi

# there are 15 verify commands
if [[ $DKR_RUN_VERIFY == "false" ]]; then
  echo 'Will NOT run verification commands.'
else
  echo 'Running verification commands...'
  php artisan firefly-iii:fix-piggies
  php artisan firefly-iii:create-link-types
  php artisan firefly-iii:create-access-tokens
  php artisan firefly-iii:remove-bills
  php artisan firefly-iii:fix-negative-limits
  php artisan firefly-iii:enable-currencies
  php artisan firefly-iii:fix-transfer-budgets
  php artisan firefly-iii:fix-uneven-amount
  php artisan firefly-iii:delete-zero-amount
  php artisan firefly-iii:delete-orphaned-transactions
  php artisan firefly-iii:delete-empty-journals
  php artisan firefly-iii:delete-empty-groups
  php artisan firefly-iii:fix-account-types
  php artisan firefly-iii:fix-account-order
  php artisan firefly-iii:rename-meta-fields
  php artisan firefly-iii:fix-ob-currencies
  php artisan firefly-iii:fix-long-descriptions
  php artisan firefly-iii:fix-recurring-transactions
  php artisan firefly-iii:unify-group-accounts
  php artisan firefly-iii:fix-transaction-types
  php artisan firefly-iii:fix-frontpage-accounts
  php artisan firefly-iii:fix-ibans
  php artisan firefly-iii:create-group-memberships
  php artisan firefly-iii:upgrade-group-information

fi

# report commands
if [[ $DKR_RUN_REPORT == "false" ]]; then
  echo 'Will NOT run report commands.'
else
  echo 'Running report commands...'
  php artisan firefly-iii:report-empty-objects
  php artisan firefly-iii:report-sum
fi

php artisan firefly-iii:restore-oauth-keys

if [[ $DKR_RUN_PASSPORT_INSTALL == "false" ]]; then
  echo 'Will NOT generate new OAuth keys.'
else
  echo 'Generating new OAuth keys...'
  php artisan passport:install
fi

php artisan firefly-iii:set-latest-version --james-is-cool
php artisan cache:clear > /dev/null 2>&1
php artisan config:cache > /dev/null 2>&1

# set docker var.
export IS_DOCKER=true

php artisan firefly-iii:verify-security-alerts
php artisan firefly:instructions install

if [ -z $APACHE_RUN_USER ]
then
      APACHE_RUN_USER='www-data'
fi

if [ -z $APACHE_RUN_GROUP ]
then
      APACHE_RUN_GROUP='www-data'
fi

rm -rf /var/www/html/storage/framework/cache/data/*
rm -f /var/www/html/storage/logs/*.log
chown -R $APACHE_RUN_USER:$APACHE_RUN_GROUP /var/www/html/storage
chmod -R 775 /var/www/html/storage

echo "You can safely ignore the error about the 'fully qualified domain name'."
exec apache2-foreground
