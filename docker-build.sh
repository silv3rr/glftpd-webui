#!/bin/bash
VERSION=V4
################################## ################################   ####  # ##
# >> DOCKER-BUILD-GLFTPD :: WEBUI
################################## ################################   ####  # ##
#
# ARGS+= " --any-flags " adds any docker build options
#
##################################################################   ####  ## ##

BUILD_GLFTPD=0
INSTALL_WEBUI=1

# set glftpd version
GLFTPD_URL="${GLFTPD_URL:-"https://mirror.glftpd.nl.eu.org/glftpd-LNX-2.14a_3.0.12_x64.tgz"}"
GLFTPD_SHA="${GLFTPD_SHA:-"981fec98d3c92978f8774a864729df0a2bca91afc0672c51833f0cfc10ac04935ccaadfe9798a02711e3a1c4c714ddd75d5edd5fb54ff46ad495b1a2c391c1ad"}"
GLFTPD_VER="$( basename "$GLFTPD_URL" | sed 's/^glftpd.*-\([0-9\.]\+[a-z]\?\)_.*/\1/' )"

ARGS+="$*"

echo "----------------------------------------------"
echo "DOCKER-GLFTPD-BUILD-${VERSION}"
echo "----------------------------------------------"

if [ "${BUILD_GLFTPD:-0}" -eq 1 ]; then
  echo "Build image: 'docker-glftpd'"
  echo "* you can ignore any cache errors"
  TAG="latest"
  if [ "${INSTALL_WEBUI:-0}" -eq 1 ] && [ "${INSTALL_ZS:-0}" -eq 1 ] && [ "${INSTALL_BOT:-0}" -eq 1 ]; then
    TAG="full"
  fi
  # shellcheck disable=SC2086
  docker build \
    $ARGS \
    --cache-from "docker-glftpd:${TAG}" \
    --tag "docker-glftpd:${TAG}" \
    --tag "docker-glftpd:${GLFTPD_VER:-2}" \
    --build-arg GLFTPD_URL="${GLFTPD_URL}" \
    --build-arg GLFTPD_SHA="${GLFTPD_SHA}" \
    --build-arg INSTALL_BOT="${INSTALL_BOT:-0}" \
    --build-arg INSTALL_ZS="${INSTALL_ZS:-0}" \
    --build-arg INSTALL_WEBUI="${INSTALL_WEBUI:-0}" \
    --build-arg http_proxy="${http_proxy:-$HTTP_PROXY}" \
    https://github.com/silv3rr/docker-glftpd.git
fi

if [ "${INSTALL_WEBUI:-1}" -eq 1 ]; then
  echo "Build image 'docker-glftpd-web'"
  # shellcheck disable=SC2086
  docker build \
    $ARGS \
    --file Dockerfile \
    --cache-from "docker-glftpd-web:latest" \
    --tag "docker-glftpd-web:latest" \
    --build-arg WEBUI_CERT="${WEBUI_CERT:-1}" \
    --build-arg http_proxy="${http_proxy:-$HTTP_PROXY}" \
    .
elif [ "${INSTALL_WEBUI:-1}" -eq 2 ]; then
  echo "Build image 'docker-glftpd-web-debian'"
  # shellcheck disable=SC2086
  docker build \
    $ARGS \
    --file Dockerfile-debian \
    --no-cache \
    --tag "docker-glftpd-web:latest" \
    --build-arg WEBUI_CERT="${WEBUI_CERT:-1}" \
    --build-arg http_proxy="${http_proxy:-$HTTP_PROXY}" \
    .
fi
