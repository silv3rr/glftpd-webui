###################################################################   ####  # ##
# >>  DOCKERFILE-GLFTPD-WEBUI
###################################################################   ####  # ##

# install nginx, php and gl webui app

ARG WEBUI_PORT
ARG WEBUI_CERT
ARG WEBUI_AUTHMODE
ARG WEBUI_USERNAME
ARG WEBUI_PASSWORD
FROM alpine:3.18
HEALTHCHECK CMD wget -qO /dev/null http://127.0.0.1/health
LABEL org.opencontainers.image.source=https://github.com/silv3rr/glftpd-webui
LABEL org.opencontainers.image.description="Web-gui to manage glftpd"
EXPOSE ${WEBUI_PORT:-443}
WORKDIR /app
COPY --chown=0:0 bin/auth.sh /
COPY --chown=0:0 etc/nginx /etc/nginx
COPY --chown=0:0 bin/gltool.sh /usr/local/bin
COPY --chown=0:0 bin/gotty /usr/local/bin
COPY --chown=0:0 bin/passchk /usr/local/bin
COPY --chown=0:0 bin/pywho /usr/local/bin
COPY --chown=0:0 bin/spy /usr/local/bin
COPY --chown=100:101 assets/ /app/
COPY --chown=100:101 lib/auth/ /auth/lib/
COPY --chown=100:101 lib/ui/ /app/lib/
COPY --chown=100:101 lib/webspy/ /usr/local/bin/webspy/
COPY --chown=100:101 src/ui /app/
COPY --chown=100:101 src/auth /auth/
COPY --chown=100:101 templates/ /app/templates/
#ADD --chown=100:101 fontawesome-free-6.5.1-web.tar.gz /app/lib
SHELL ["/bin/ash", "-eo", "pipefail", "-c"]
#sed -i 's/https/http/g' /etc/apk/repositories ;\
# hadolint ignore=SC2016,SC2086,DL3018
RUN test -n "$http_proxy" && { \
      http_proxy=${http_proxy}; \
      https_proxy=${http_proxy}; \
    }; \
    apk add --no-cache \
      nginx \
      php \
      php-fpm \
      php-session \
      php-ftp \
      php-curl \
      php-json \
      php-ctype \
      apache2-utils \
      openssl \
      bash \
      grep \
      sed \
      gawk \
      sudo && \
    rm -rf /var/cache/apk/* && \
    install -d -m 0755 -o nginx -g nginx /run/nginx && \
    install -d -m 0755 -o nginx -g nginx /run/php && \
    rm /etc/nginx/http.d/default.conf && \
    ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log && \
    sed -i 's|listen = 127.0.0.1:9000|listen = /run/php/php-fpm.sock|' /etc/php*/php-fpm.d/www.conf && \
    sed -i 's|;listen.owner = nobody|listen.owner = nginx|' /etc/php*/php-fpm.d/www.conf && \
    sed -i 's|;listen.group = nobody|listen.owner = nginx|' /etc/php*/php-fpm.d/www.conf && \
    # generate self-signed cert
    if [ "${WEBUI_CERT:-1}" -eq 1 ]; then \
      if [ ! -e /etc/nginx/webui.crt ] && [ ! -e /etc/nginx/webui.key ]; then \
        openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
          -config /etc/nginx/webui.cnf \
          -keyout /etc/nginx/webui.key \
          -out /etc/nginx/webui.crt && \
        chmod 600 /etc/nginx/webui.key; \
      fi; \
    fi && \
    chown 0:0 /auth && \
    echo 'shit:$apr1$8kedvKJ7$PuY2hy.QQh6iLP3Ckwm740' > /etc/nginx/.htpasswd && \
    { echo 'Cmnd_Alias SYSTEMCTL = /bin/systemctl start glftpd.socket, /bin/systemctl stop glftpd.socket /bin/systemctl start glftpd.socket'; \
      echo 'Cmnd_Alias SERVICE = /sbin/service glftpd start, /sbin/service glftpd stop, /sbin/service glftpd start'; \
      echo 'Cmnd_Alias PKILL = /usr/bin/pkill -9 -f glftpd'; \
      echo 'Cmnd_Alias KILLALL = /usr/bin/killall -9 gotty >/dev/null 2>&1, /usr/bin/killall -9 gl_spy >/dev/null 2>&1, /usr/bin/killall -9 useredit >/dev/null 2>&1'; \
      echo 'Cmnd_Alias BUSYBOX = /bin/busybox killall -9 gotty >/dev/null 2>&1, /bin/busybox killall -9 gl_spy >/dev/null 2>&1, /bin/busybox killall -9 useredit >/dev/null 2>&1'; \
      echo 'Cmnd_Alias GLTOOL = /glftpd/bin/gltool.sh, /jail/glftpd/bin/gltool.sh, /usr/local/bin/gltool.sh'; \
      echo 'nobody ALL = (root) NOPASSWD: SYSTEMCTL, SERVICE, PKILL, KILLALL, BUSYBOX, HASHGEN, PASSCHK, GLTOOL'; \
    } > /etc/sudoers.d/glftpd-web && \    
    addgroup nobody ping || true && \
    { echo '#!/bin/sh -x'; \
      echo 'DOCKER_GID="$( stat -c %g /var/run/docker.sock )"'; \
      echo 'grep -Eq "^docker:" /etc/group || echo "docker:x:${DOCKER_GID:-999}:nobody" >>/etc/group'; \
      echo '{ nslookup -type=a glftpd-web 127.0.0.11 | grep -A 99 answer:; } | grep -q Address || echo "127.0.0.1 glftpd-web" >>/etc/hosts' ;\
      echo '{ nslookup -type=a glftpd 127.0.0.11 | grep -A 99 answer:; } | grep -q Address || echo "127.0.0.1 glftpd" >>/etc/hosts' ;\
      echo 'cp -rf /etc/nginx/http.d/auth.conf.template /etc/nginx/http.d/auth.conf';  \
      echo 'cp -rf /etc/nginx/http.d/webui.conf.template /etc/nginx/http.d/webui.conf'; \
      echo 'sed -i "s/\( *listen\) .* ssl;$/\1 ${WEBUI_PORT:-443} ssl;/" /etc/nginx/http.d/webui.conf'; \
      echo 'chown 65534:root /app/config.php'; \
      #echo 'USERNAME="$WEBUI_USERNAME" PASSWORD="$WEBUI_PASSWORD" AUTH="$WEBUI_AUTHMODE" /auth.sh'; \
      echo '$(echo /usr/sbin/php-fpm*) -F &'; \
      echo 'nginx -g "daemon off;"'; \
    } >/entrypoint.sh && \
    chmod +x /entrypoint.sh
ENTRYPOINT [ "/entrypoint.sh" ]
