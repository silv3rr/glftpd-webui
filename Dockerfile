###################################################################   ####  # ##
# >>  DOCKERFILE-GLFTPD-WEBUI :: WEBUI
###################################################################   ####  # ##

# alpine (default)
# user: nginx uid=100 gid=101 home=/var/lib/nginx

# install nginx, php and gl webui app

ARG WEBUI_PORT
ARG WEBUI_CERT

FROM alpine:3.18
HEALTHCHECK CMD busybox wget -qO /dev/null http://127.0.0.1/health
ARG PHP_VER="${PHP_VER:-82}"
LABEL org.opencontainers.image.source=https://github.com/silv3rr/glftpd-webui
LABEL org.opencontainers.image.description="Web-gui to manage glftpd"
EXPOSE ${WEBUI_PORT:-443}
WORKDIR /app
# nginx=100:101 nobody=65534:65534 ping=999:999
COPY --chown=0:0 bin/entrypoint.sh /
COPY --chown=0:0 bin/auth.sh /
COPY --chown=0:0 etc/sudoers.d/glftpd-web /etc/sudoers.d/
COPY --chown=0:0 etc/nginx /etc/nginx
COPY --chown=0:0 etc/nginx/http.d/webui.conf.template /etc/nginx/http.d/webui.conf
COPY --chown=0:0 etc/nginx/http.d/auth-server.conf.template /etc/nginx/http.d/auth-server.conf
COPY --chown=0:0 etc/nginx/auth.d/auth_off.conf.template /etc/nginx/auth.d/auth_off.conf
COPY --chown=0:0 etc/nginx/auth.d/auth_basic.conf.template /etc/nginx/auth.d/auth_basic.conf
COPY --chown=0:0 bin/gltool.sh bin/gotty bin/alpine/passchk bin/alpine/pywho bin/alpine/spy etc/pywho.conf etc/spy.conf /usr/local/bin/
COPY --chown=0:0 etc/dot_gotty /var/lib/nginx/.gotty
#COPY --chown=0:0 etc/webspy/ /usr/local/bin/webspy/
COPY --chown=100:101 assets/ /app/assets/
COPY --chown=100:101 lib/ /app/lib/
COPY --chown=100:101 src/ui /app/
COPY --chown=100:101 src/config.php.dist /app/config.php
COPY --chown=100:101 templates/ /app/templates/
COPY --chown=100:101 src/auth /auth/
COPY --chown=100:101 README.md docs /app/templates/
SHELL ["/bin/ash", "-eo", "pipefail", "-c"]
# hadolint ignore=SC2016,SC2086,DL3018
RUN test -n "$http_proxy" && { \
    http_proxy=${http_proxy}; \
    https_proxy=${http_proxy}; \
  }; \
  test -n "$apk_http" && sed -i 's/https/http/g' /etc/apk/repositories; \
  echo DEBUG php${PHP_VER} ; \
  apk add --no-cache \
    nginx \
    php${PHP_VER} \
    php${PHP_VER}-fpm \
    php${PHP_VER}-session \
    php${PHP_VER}-ftp \
    php${PHP_VER}-curl \
    php${PHP_VER}-json \
    php${PHP_VER}-ctype \
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
  sed -i 's/;listen.\(owner\|group\) = nobody/listen.\1 = nginx/' /etc/php*/php-fpm.d/www.conf && \
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
  chown 65534:root /etc/nginx/.htpasswd && \
  addgroup nobody ping && \
  rm -rf /tmp/* /var/tmp/*
ENTRYPOINT [ "/entrypoint.sh" ]
