#!/bin/sh
set -eu

# Create the isolated database used by the test suite. This runs only when the
# Postgres data volume is first initialised.
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE ecommerce_test OWNER "$POSTGRES_USER";
EOSQL
