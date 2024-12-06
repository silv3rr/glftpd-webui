#!/bin/sh

# glftpd-webui::docker::entrypoint

DOCKER_GID="$(stat -c %g /var/run/docker.sock 2>/dev/null)"
grep -Eq "^docker:" /etc/group || echo "docker:x:${DOCKER_GID:-999}:nobody" >>/etc/group

echo "Looking up glftpd host... "
{ nslookup -type=a glftpd-web 127.0.0.11 | grep -A 99 answer:; } | grep -q Address || \
    echo "127.0.0.1 glftpd-web" | tee -a /etc/hosts
echo "Looking up web host... "
{ nslookup -type=a glftpd 127.0.0.11 | grep -A 99 answer:; } | grep -q Address || \
    echo "127.0.0.1 glftpd" | tee -a /etc/hosts

test -s /app/config.php || cp -f /app/config.php.dist /app/config.php
chown 65534:root /app/config.php

cp -f /etc/nginx/http.d/webui.conf.template /etc/nginx/http.d/webui.conf
sed -i "s/\( *listen\) .* ssl;$/\1 ${WEBUI_PORT:-443} ssl;/" /etc/nginx/http.d/webui.conf
test -x /auth.sh && /auth.sh

if [ ! -S /var/run/php/php-fpm.sock ]; then
    { sleep 2; \
        PHP_FPM_SOCK=$(echo /var/run/php/php*-fpm.sock); \
        test -S "$PHP_FPM_SOCK" && ln -s "$PHP_FPM_SOCK" /var/run/php/php-fpm.sock
    } &
fi
$(echo /usr/sbin/php-fpm*) -F &
nginx -g "daemon off;"
