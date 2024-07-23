###################################################################   ####  # ##
# >>  DOCKERFILE-GLFTPD-WEBUI :: WEBUI
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
COPY --chown=0:0 bin/entrypoint.sh /
COPY --chown=0:0 bin/auth.sh /
COPY --chown=0:0 etc/sudoers.d/glftpd-web /etc/sudoers.d
COPY --chown=0:0 etc/nginx /etc/nginx
COPY --chown=0:0 bin/gltool.sh bin/gotty bin/passchk bin/pywho bin/spy /usr/local/bin/
COPY --chown=100:101 assets/ /app/assets/
COPY --chown=100:101 lib/ui/ /app/lib/
COPY --chown=100:101 lib/webspy/ /usr/local/bin/webspy/
COPY --chown=100:101 src/ui /app/
COPY --chown=100:101 templates/ /app/templates/
COPY --chown=100:101 src/auth /auth/
COPY --chown=100:101 lib/auth/ /auth/lib/
#COPY --chown=100:101 assets/css/ /auth/assets/css/
#COPY --chown=100:101 lib/ui/bootstrap-4.6.2-dist/css/ /auth/lib/bootstrap-4.6.2-dist/css/
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
    addgroup nobody ping && \
    rm -rf /tmp/* /var/tmp/*
ENTRYPOINT [ "/entrypoint.sh" ]
