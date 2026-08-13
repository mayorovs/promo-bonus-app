#!/bin/sh
# Creates a dedicated test database alongside the development one.
# Tests run against PostgreSQL rather than SQLite so that database-level
# guarantees (partial unique indexes, row locking) behave as in production.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE ${POSTGRES_DB}_testing OWNER $POSTGRES_USER;
EOSQL
