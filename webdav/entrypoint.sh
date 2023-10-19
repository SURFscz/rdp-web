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

LOCATION=${LOCATION:-/}

PAM_URL=${PAM_URL:-https://sram.surf.nl}
PAM_REDIS=${PAM_REDIS:-localhost}
PAM_TOKEN=${PAM_TOKEN:-none}
PAM_ENTITLED=${PAM_ENTITLED:-*}

SERVERNAME=${SERVERNAME:-localhost}

# Configure ServerName.
sed -e "s|Define SERVERNAME .* |Define SERVERNAME $SERVERNAME|" \
    -i "$HTTPD_PREFIX"/conf/sites-enabled/default.conf
echo "ServerName $SERVERNAME:80" >> "$HTTPD_PREFIX"/conf/httpd.conf

# Configure dav.conf
if [ "x$LOCATION" != "x" ]; then
    sed -e "s|Define LOCATION .*|Define LOCATION $LOCATION|" \
        -i "$HTTPD_PREFIX/conf/sites-enabled/default.conf"
fi

usermod -u ${PUID} ${WWW_DATA}
groupmod -g ${PGID} ${WWW_DATA}

mkdir -p /var/www/webdav && chown "${WWW_DATA}":"${WWW_DATA}" /var/www/webdav
mkdir -p /usr/local/apache/var/ && chown "${WWW_DATA}":"${WWW_DATA}" /usr/local/apache/var

cat << EOF > /etc/pam.d/pam-service
auth required pam_sram_validate.so debug url=${PAM_URL} token=${PAM_TOKEN} entitled=${PAM_ENTITLED} redis=${PAM_REDIS}
account sufficient pam_permit.so
EOF

syslogd
redis-server --daemonize yes

# Set umask
if [ "x$PUMASK" != "x" ]; then
    umask $PUMASK
fi

exec "$@"
