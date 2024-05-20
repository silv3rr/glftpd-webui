#!/bin/bash

################################## ################################   ####  # ##
# >> DOCKER-RUN-GLFTPD-WEBGUI
################################## ################################   ####  # ##

GLDIR=/glftpd
GLFTPD=0
LOCAL=1

#NETWORK="$(docker network ls --format '{{.Name}}' --filter 'Name=shit')"

# shellcheck disable=SC2288
WEBUI_ARGS+= " --env WEBUI_AUTHMODE=none "

#GLFTPD_ARGS="$*"
WEBUI_ARGS=""
NETWORK_ARGS="--network shit"
GLFTPD_ARGS+=" $NETWORK_ARGS "
#WEBUI_ARGS+=" $NETWORK_ARGS "

#WEBUI_ARGS+=" --volume ${GLDIR:-/glftpd}:/glftpd "

if [ "${LOCAL:-0}" -eq 1 ]; then
  WEBUI_ARGS+=" $NETWORK_ARGS "
  WEBUI_ARGS+=" --volume ${GLDIR:-/glftpd}:/glftpd "
else
  WEBUI_ARGS+="--network host"
fi

WEBUI_ARGS+=" --volume /var/run/docker.sock:/var/run/docker.sock "
RM=1

DOCKER_IMAGE_GLFTPD="docker-glftpd:latest"
DOCKER_IMAGE_WEBUI="docker-glftpd-web:latest"
DOCKER_REGISTRY="ghcr.io/silv3rr"

SCRIPTDIR="$(dirname "$0")"

# get external/public ip
if [ -z "$IP_ADDR" ]; then
  # TODO: ip route show to exact 0/0 
  GET_IP="$( ip route get "$(ip route show 0.0.0.0/0 | grep -oP 'via \K\S+')" | grep -oP 'src \K\S+' )"
  IP_ADDR="${GET_IP:-127.0.0.1}"
fi

ZS_STATUS="$(
  docker image inspect --format='{{ index .Config.Labels "gl.zipscript.setup" }}' "$DOCKER_IMAGE_GLFTPD" \
    2>/dev/null
)"

BOT_STATUS="$(
  docker image inspect --format='{{ index .Config.Labels "gl.sitebot.setup" }}' "$DOCKER_IMAGE_GLFTPD" \
    2>/dev/null
)"

if [ -s "$SCRIPTDIR/customizer.sh" ]; then
  IP_ADDR=$IP_ADDR ZS_STATUS=$ZS_STATUS BOT_STATUS=$BOT_STATUS \
  GLFTPD_CONF=$GLFTPD_CONF GLFTPD_PERM_UDB=$GLFTPD_PERM_UDB GLFTPD_PORT=$GLFTPD_PORT \
  GLFTPD_PASV_PORTS=$GLFTPD_PASV_PORTS GLFTPD_PASV_ADDR=$GLFTPD_PASV_ADDR \
  IRC_SERVERS=$IRC_SERVERS IRC_CHANNELS=$IRC_CHANNELS \
  "$SCRIPTDIR/customizer.sh"
else
  echo "! Skipping custom config, 'customizer.sh' not found"
fi

echo "----------------------------------------------"
echo "DOCKER-GLFTPD-RUN-V3"
echo "----------------------------------------------"

# select image

LOCAL_IMAGE=$(
  docker image ls --format='{{.Repository}}' --filter reference="$DOCKER_IMAGE_GLFTPD"
)
LOCAL_FULL_IMAGE=$(
  docker image ls --format='{{.Repository}}' --filter reference="${DOCKER_IMAGE_GLFTPD/%:latest/:full}"
)
if [ -n "$LOCAL_IMAGE" ] && [ "${USE_FULL:-0}" -eq 0 ]; then
  echo "* Found local image 'docker-glftpd'"
elif [ -n "$LOCAL_FULL_IMAGE" ]; then
  DOCKER_IMAGE_GLFTPD="${DOCKER_IMAGE_GLFTPD/%:latest/:full}"
  echo "* Using full docker image ${LOCAL_FULL_IMAGE:-""})"
else
  # check if we already have 'full' tagged image, then keep using it
  REGISTRY_FULL_IMAGE=$(
    docker image ls --format='{{.Repository}}' --filter reference="${DOCKER_REGISTRY}/${DOCKER_IMAGE_GLFTPD/%:latest/:full}"
  )
  if [ -n "$REGISTRY_FULL_IMAGE" ] || [ "${USE_FULL:-0}" -eq 1 ]; then
    DOCKER_IMAGE_GLFTPD="${DOCKER_IMAGE_GLFTPD/%:latest/:full}"
  fi
  echo "* Pulling image '${DOCKER_IMAGE_GLFTPD}' from registry '$DOCKER_REGISTRY'"
  DOCKER_IMAGE_GLFTPD="${DOCKER_REGISTRY}/$DOCKER_IMAGE_GLFTPD"
  docker pull "$DOCKER_IMAGE_GLFTPD"
fi

# set runtime docker args

# set max open files to prevent high cpu usage by some procs
GLFTPD_ARGS+=" --ulimit nofile=1024:1024 "
WEBUI_ARGS+=" --ulimit nofile=1024:1024 "

if [ "${GLFTPD_CONF:-0}" -eq 1 ] || [ "${ZS_STATUS:-0}" -eq 1 ]; then
  RM=0
  if [ -d glftpd/glftpd.conf ]; then
    rmdir glftpd/glftpd.conf 2>/dev/null || { echo "ERROR: \"glftpd.conf\" is a directory, remove it manually"; }
  fi
  if [ -f glftpd/glftpd.conf ]; then
    GLFTPD_ARGS+=" --mount type=bind,src=${GLDIR:-/glftpd}/glftpd.conf,dst=/glftpd/glftpd.conf "
    WEBUI_ARGS+=" --mount type=bind,src=${GLDIR:-/glftpd}/glftpd.conf,dst=/app/glftpd/glftpd.conf"
  fi
fi

if [ "${GLFTPD_CONF:-0}" -eq 1 ]; then
  RM=0
  echo "* Set docker ip:port"
  #GLFTPD_PASV_PORTS="$(sed -n -E 's/^pasv_addr (.*)/\1/p' glftpd/glftpd.conf)"
  if grep -Eq "^pasv_ports.*" glftpd/glftpd.conf; then
    GLFTPD_ARGS+=" --publish ${IP_ADDR}:${GLFTPD_PASV_PORTS:-5000-5100}:${GLFTPD_PASV_PORTS:-5000-5100} "
  fi
fi

if [ -n "$GLFTPD_PASSWD" ]; then
  GLFTPD_ARGS+=" --env GLFTPD_PASSWD=$GLFTPD_PASSWD "
fi

if [ "${GLFTPD_PERM_UDB:-0}" -eq 1 ]; then
  RM=0
  GLFTPD_ARGS+=" --mount type=bind,src=${GLDIR:-/glftpd}/ftp-data/users,dst=/glftpd/ftp-data/users "
  GLFTPD_ARGS+=" --mount type=bind,src=${GLDIR:-/glftpd}/ftp-data/groups,dst=/glftpd/ftp-data/groups"
  GLFTPD_ARGS+=" --mount type=bind,src=${GLDIR:-/glftpd}/etc,dst=/glftpd/etc "
fi

# shellcheck disable=SC2174
if [ "${GLFTPD_SITE:-0}" -eq 1 ]; then
  GLFTPD_ARGS+=" --volume ${GLDIR:-/glftpd}/site:/glftpd/site:rw "
  WEBUI_ARGS+=" --mount type=bind,src=${GLDIR:-/glftpd}/site,dst=/app/glftpd/site "
else
  WEBUI_ARGS+=" --mount type=tmpfs,dst=/app/glftpd/site/NO_BIND_MOUNT " 
fi

if [ "${BOT_STATUS:-0}" -eq 1 ]; then
  RM=0
  GLFTPD_ARGS+=" --mount type=bind,src=${GLDIR:-/glftpd}/sitebot,dst=/glftpd/sitebot "
  GLFTPD_ARGS+=" --publish ${IP_ADDR}:3333:3333 "
  for i in glftpd/sitebot/eggdrop.conf glftpd/sitebot/pzs-ng/ngBot.conf ; do
    if [ -d "$i" ]; then
      rmdir "$i" 2>/dev/null || { echo "ERROR: \"$i\" is a directory, remove it manually"; }
    fi
  done
  if [ -f glftpd/sitebot/eggdrop.conf ]; then
    WEBUI_ARGS+=" --mount type=bind,src=${GLDIR:-/glftpd}/sitebot/eggdrop.conf,dst=/app/glftpd/sitebot/eggdrop.conf "
  fi
  if [ -f glftpd/sitebot/pzs-ng/ngBot.conf ]; then
    WEBUI_ARGS+=" --mount type=bind,src=${GLDIR:-/glftpd}/sitebot/pzs-ng/ngBot.conf,dst=/app/glftpd/sitebot/pzs-ng/ngBot.conf "
  fi
fi

if [ -d entrypoint.d ]; then
  RM=0
  GLFTPD_ARGS+=" --mount type=bind,src=$(pwd)/entrypoint.d,dst=/entrypoint.d "
  echo "* Mount 'entrypoint.d' dir for custom commands"
fi

if [ -d custom ]; then
  RM=0
  if find custom/* >/dev/null 2>&1; then
    GLFTPD_ARGS+=" --mount type=bind,src=$(pwd)/custom,dst=/custom "
    echo "* Found files in 'custom', mounting dir"
  fi
fi

if [ "${RM:-1}" -eq 1 ]; then
  GLFTPD_ARGS+=" --rm  "
fi

# remove existing containers which use local and/or registry images

#set -x
if [ "${WEBUI:-0}" -eq 1 ]; then
  REGEX="( ${DOCKER_IMAGE_GLFTPD:-'docker-glftpd'}|${DOCKER_REGISTRY}/docker-glftpd)$"
else
  REGEX="(glftpd|glftpd-web|( ${DOCKER_IMAGE_GLFTPD:-'docker-glftpd'}|${DOCKER_IMAGE_WEBUI:-'docker-glftpd-web'}|${DOCKER_REGISTRY}/docker-glftpd.*))$"
fi
docker ps -a --format '{{.ID}} {{.Image}} {{.Names}}'| grep -E "$REGEX" | while read -r i; do
  CONTAINER="$(echo "$i"|cut -d' ' -f1)"
  if [ -n "$CONTAINER" ] && [ "${FORCE:-0}" -eq 1 ]; then
    printf "* Removing existing container '%s'... " "$i"
    docker rm -f -v "$CONTAINER" 2>/dev/null
  else
    echo "WARNING: container '$i' already exists, to remove it: 'FORCE=1 ./docker-run.sh'"
  fi
done

# run docker with glftpd image and GLFTPD_ARGS

# shellcheck disable=SC2086
if [ "${GLFTPD:-1}" -eq 1 ]; then
  if [ -n "$DOCKER_IMAGE_GLFTPD" ]; then
    printf "* Docker run '%s'... " "$DOCKER_IMAGE_GLFTPD"
    docker run \
      $GLFTPD_ARGS \
      --detach \
      --name glftpd \
      --hostname glftpd \
      --publish "${IP_ADDR}:${GLFTPD_PORT:-1337}:1337" \
      --workdir /glftpd \
      $DOCKER_IMAGE_GLFTPD
    echo "* For logs run 'docker logs glftpd'"
  fi
fi

# run optional web interface

if [ "${WEBUI:-1}" -eq 1 ]; then
  LOCAL_IMAGE_WEBUI=$(
    docker image ls --format='{{.Repository}}' --filter reference="$DOCKER_IMAGE_WEBUI"
  )
  if [ -n "$LOCAL_IMAGE_WEBUI" ]; then
    echo "* Using local docker image for webui"
  else
    echo "* Pulling image '${DOCKER_IMAGE_WEBUI}' from registry '$DOCKER_REGISTRY'"
    DOCKER_IMAGE_WEBUI="${DOCKER_REGISTRY}/${DOCKER_IMAGE_WEBUI}"
    docker pull $DOCKER_IMAGE_WEBUI
  fi
  # shellcheck disable=SC2086
  if [ -n "$DOCKER_IMAGE_WEBUI" ]; then
    if [ "${RM:-1}" -eq 1 ]; then
      WEBUI_ARGS+=" --rm  "
    fi
    printf "* Docker run '%s'... " "$DOCKER_IMAGE_WEBUI"
    #--detach \
      
    docker run \
      $WEBUI_ARGS \
      --hostname glftpd-web \
      --name glftpd-web \
      --publish "${IP_ADDR:-127.0.0.1}:4444:443" \
      $DOCKER_IMAGE_WEBUI
  fi
  echo "* For logs run 'docker logs glftpd-web'"
fi

echo "* All done."
