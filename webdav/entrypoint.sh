#!/bin/sh
set -e

# Environment variables that are used if not empty:
#   SERVER_NAME
#   LOCATION
#   PUID
#   PGID
#   PUMASK

# Just in case this environment variable has gone missing.
HTTPD_PREFIX="${HTTPD_PREFIX:-/usr/local/apache2}"
PUID=${PUID:-1000}
PGID=${PGID:-1000}

SERVERNAME=${SERVERNAME:-localhost}

# Configure ServerName.
sed -e "s|ServerName .*|ServerName $SERVERNAME|" \
    -i "$HTTPD_PREFIX"/conf/sites-enabled/default.conf
echo "ServerName $SERVERNAME:80" >> "$HTTPD_PREFIX"/conf/httpd.conf

# Configure dav.conf
if [ "x$LOCATION" != "x" ]; then
    sed -e "s|Alias .*|Alias $LOCATION /var/www/webdav/|" \
        -i "$HTTPD_PREFIX/conf/sites-enabled/default.conf"
fi

usermod -u ${PUID} ${WWW_DATA}
groupmod -g ${PGID} ${WWW_DATA}

mkdir -p /var/www/webdav && chown "${WWW_DATA}":"${WWW_DATA}" /var/www/webdav
mkdir -p /usr/local/apache/var/ && chown "${WWW_DATA}":"${WWW_DATA}" /usr/local/apache/var

# Set umask
if [ "x$PUMASK" != "x" ]; then
    umask $PUMASK
fi

exec "$@"
