#!/usr/bin/env bash
#
# Install WordPress and the PHPUnit test library for local / CI runs.
#
# Usage:
#   bash tests/bin/install-wp-tests.sh [db-name] [db-user] [db-pass] [db-host] [wp-version]
#
# Defaults: wordpress_test root '' localhost latest

set -e

if [ -t 1 ]; then
	GREEN='\033[0;32m'
	RED='\033[0;31m'
	NC='\033[0m'
else
	GREEN=''
	RED=''
	NC=''
fi

DB_NAME=${1:-wordpress_test}
DB_USER=${2:-root}
DB_PASS=${3:-''}
DB_HOST=${4:-localhost}
WP_VERSION=${5:-latest}
TMPDIR=${TMPDIR:-/tmp}
WP_TESTS_DIR=${WP_TESTS_DIR:-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-$TMPDIR/wordpress/}

download() {
	if command -v curl >/dev/null 2>&1; then
		curl -sL "$1" -o "$2"
	elif command -v wget >/dev/null 2>&1; then
		wget -q "$1" -O "$2"
	else
		echo -e "${RED}curl or wget required.${NC}"
		exit 1
	fi
}

if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
	WP_TESTS_TAG="tags/$WP_VERSION"
else
	WP_TESTS_TAG='trunk'
fi

install_wp() {
	if [ -d $WP_CORE_DIR ]; then
		return
	fi

	mkdir -p $WP_CORE_DIR
	download https://wordpress.org/latest.tar.gz /tmp/wordpress.tar.gz
	tar --strip-components=1 -zxf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
	rm -f /tmp/wordpress.tar.gz
}

install_test_suite() {
	if [ -d $WP_TESTS_DIR ]; then
		return
	fi

	mkdir -p $WP_TESTS_DIR
	svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
	svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
	download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR/wp-tests-config.php"

	sed -i.bak "s:dirname( __FILE__ ) . '/src/':dirname( __FILE__ ) . '/../wordpress/':" "$WP_TESTS_DIR/wp-tests-config.php"
	sed -i.bak "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
	sed -i.bak "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
	sed -i.bak "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
	sed -i.bak "s/localhost/$DB_HOST/" "$WP_TESTS_DIR/wp-tests-config.php"
	rm -f "$WP_TESTS_DIR/wp-tests-config.php.bak"
}

install_db() {
	local PARTS=(${DB_HOST//:/ })
	local DB_HOSTNAME=${PARTS[0]}
	local DB_SOCK=""
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] && [ "$(echo $DB_HOSTNAME | tr -d '[:digit:]')" != "" ]; then
		if [ ! -z $DB_SOCK ]; then
			EXTRA=" --socket=$DB_SOCK"
		elif [ ! -z "$DB_PORT" ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_PORT --protocol=tcp"
		fi
	fi

	if mysql --user="$DB_USER" --password="$DB_PASS" $EXTRA --execute="USE $DB_NAME" 2>/dev/null; then
		return
	fi

	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS" $EXTRA
}

install_wp
install_test_suite
install_db

export WP_TESTS_DIR
export WP_CORE_DIR

echo -e "${GREEN}WordPress test suite ready.${NC}"
echo "WP_TESTS_DIR=$WP_TESTS_DIR"
echo "WP_CORE_DIR=$WP_CORE_DIR"
