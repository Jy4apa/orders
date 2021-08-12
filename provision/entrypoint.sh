#!/usr/bin/env bash
set -e

cd server

# check env variables
echo "### STEP: 1 -- Check env variables"
ERRORS=0
if [[ -z ${DOMAIN_NAME} ]]; then
  echo "ERROR: Missing DOMAIN_NAME env variable"
  ERRORS=$((ERRORS + 1))
fi

if [[ -z ${POSTGRES_DB} ]]; then
  echo "ERROR: Missing POSTGRES_DB env variable"
  ERRORS=$((ERRORS + 1))
fi

if [[ -z ${POSTGRES_USER} ]]; then
  echo "ERROR: Missing POSTGRES_USER env variable"
  ERRORS=$((ERRORS + 1))
fi

if [[ -z ${POSTGRES_PASSWORD} ]]; then
  echo "ERROR: Missing POSTGRES_PASSWORD env variable"
  ERRORS=$((ERRORS + 1))
fi

if [[ -z ${DB_HOST} ]]; then
  echo "ERROR: Missing DB_HOST env variable"
  ERRORS=$((ERRORS + 1))
fi

if [[ -z ${DB_PORT} ]]; then
  echo "ERROR: Missing DB_PORT env variable"
  ERRORS=$((ERRORS + 1))
fi

if [[ $ERRORS > 0 ]]; then
    echo "DANGER: There are ${ERRORS} errors. See the console above. Exiting..."
    exit 1
fi
echo "### Ok"

# check install
echo "### STEP: 2 -- Check install"
if [[ -e "$PWD/composer.json" ]]; then
  echo "SUCCESS: Composer found in $PWD - update now..."

  composer update
  composer install

  echo "SUCCESS: Complete! Composer has been successfully updated in $PWD"
fi

# check install
echo "### STEP: 3 -- Check requirements"
if [[ -e "$PWD/requirements.php" ]]; then
  echo "SUCCESS: requirements.php found in $PWD - check now..."

  php requirements.php

  echo "SUCCESS: Complete!"
fi

# Check connect to Database
echo "### STEP: 3 -- Check connect to Database"
database_ready(){
TERM=dumb php -- <<'EOPHP'
<?php
$host = getenv("DB_HOST");
$port = getenv("DB_PORT");
$db = getenv("POSTGRES_DB");
$user = getenv("POSTGRES_USER");
$pass = getenv("POSTGRES_PASSWORD");

function connect($host, $port, $db, $user, $pass) {
    try {
    	$dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    	// make a database connection
    	$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    	if ($pdo) {
    		return $pdo;
    	}
    } catch (Exception $e) {
    	return false;
    }
}

if (connect($host, $port, $db, $user, $pass)) {
  exit(0);
} else {
  exit(1);
}

EOPHP
}

until database_ready; do
  >&2 echo 'Waiting for connecting database...'
  sleep 3
done
echo "### Ok"


echo "================================================================="
echo "Installation is complete."
echo ""
echo "Site Url: http://${DOMAIN_NAME}"
echo ""
echo "================================================================="

exec "$@"
