#!/usr/bin/env bash
#
# Installs WordPress core and the WordPress PHPUnit test library for running the plugin tests.
#
# Usage: bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
#   wp-version: "latest" (default) or a release number such as "6.3" or "7.1.4".
#
# Sources are taken from the official GitHub mirrors:
#   https://github.com/WordPress/WordPress          (built WordPress core)
#   https://github.com/WordPress/wordpress-develop  (tests/phpunit/includes and data)
#
# Environment overrides: WP_TESTS_DIR, WP_CORE_DIR, TMPDIR.

set -euo pipefail

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

TMPDIR=${TMPDIR-/tmp}
TMPDIR=${TMPDIR%/}
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}
WP_CORE_DIR=${WP_CORE_DIR%/}

if [ "$WP_VERSION" == "latest" ]; then
	WP_VERSION=$(git ls-remote --tags --refs https://github.com/WordPress/WordPress.git \
		| awk -F/ '{print $3}' | grep -E '^[0-9]+\.[0-9]+(\.[0-9]+)?$' | sort -V | tail -1)
fi

# WordPress/WordPress tags x.y.0 releases as "x.y"; wordpress-develop tags them as "x.y.0".
CORE_TAG=${WP_VERSION%.0}
if [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+$ ]]; then
	DEVELOP_TAG="$WP_VERSION.0"
else
	DEVELOP_TAG=$WP_VERSION
fi

echo "Installing WordPress $CORE_TAG into $WP_CORE_DIR and its test library into $WP_TESTS_DIR"

install_wp() {
	if [ -f "$WP_CORE_DIR/wp-settings.php" ]; then
		return
	fi
	git -c advice.detachedHead=false clone --quiet --depth 1 --branch "$CORE_TAG" https://github.com/WordPress/WordPress.git "$WP_CORE_DIR"
	rm -rf "$WP_CORE_DIR/.git"
}

install_test_suite() {
	if [ ! -d "$WP_TESTS_DIR/includes" ]; then
		local tmp
		tmp=$(mktemp -d)
		git -c advice.detachedHead=false clone --quiet --depth 1 --branch "$DEVELOP_TAG" --filter=blob:none --no-checkout \
			https://github.com/WordPress/wordpress-develop.git "$tmp"
		git -C "$tmp" sparse-checkout set --no-cone /tests/phpunit/includes/ /tests/phpunit/data/ /wp-tests-config-sample.php
		git -C "$tmp" checkout --quiet
		mkdir -p "$WP_TESTS_DIR"
		cp -R "$tmp/tests/phpunit/includes" "$tmp/tests/phpunit/data" "$WP_TESTS_DIR/"
		cp "$tmp/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config-sample.php"
		rm -rf "$tmp"
	fi

	if [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
		sed \
			-e "s:define( 'ABSPATH'.*:define( 'ABSPATH', '$WP_CORE_DIR/' );:" \
			-e "s/youremptytestdbnamehere/$DB_NAME/" \
			-e "s/yourusernamehere/$DB_USER/" \
			-e "s/yourpasswordhere/$DB_PASS/" \
			-e "s|localhost|$DB_HOST|" \
			"$WP_TESTS_DIR/wp-tests-config-sample.php" > "$WP_TESTS_DIR/wp-tests-config.php"
	fi
}

install_db() {
	if [ "$SKIP_DB_CREATE" == "true" ]; then
		return
	fi

	local host=${DB_HOST%%:*}
	local extra=""
	if [ "$host" != "$DB_HOST" ]; then
		local port_or_socket=${DB_HOST#*:}
		if [[ "$port_or_socket" =~ ^[0-9]+$ ]]; then
			extra="--port=$port_or_socket --protocol=tcp"
		else
			extra="--socket=$port_or_socket"
		fi
	elif [ -n "$host" ] && [ "$host" != "localhost" ]; then
		extra="--protocol=tcp"
	fi

	# shellcheck disable=SC2086
	mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" --host="$host" $extra 2>/dev/null \
		|| echo "Database $DB_NAME already exists or could not be created; continuing."
}

install_wp
install_test_suite
install_db
