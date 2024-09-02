# glftpd-webui (v3)

## /gl wɛb ʊi/

[![Docker](https://github.com/silv3rr/glftpd-webui/actions/workflows/docker.yml/badge.svg)](https://github.com/silv3rr/glftpd-webui/actions/workflows/docker.yml)

Web GUI for [glftpd](https://glftpd.io) (Linux only)

Shows online users, status and stops/starts glftpd. Can also be used to view logs, edit config files and browse site. All user management can be done using forms. Includes a browser terminal that displays gl_spy, useredit and bot partyline (using websockets). It should display fine on mobile devices too.

_Main page UI, click to enlarge_

[![main](docs/images/webui_small.png "Main page")](docs/images/webui.png)

## Usage

Start: `./docker-run.sh` or `docker run ghcr.io/silv3rr/glftpd-webui`

Open url: https://your.ip:4444 and login: `shit/EatSh1t`  (basic web auth).

## Setup

Pick one of these 3 options

**1**) The easiest and preferred way to use glftpd-webui is to run both glftpd and webui in 2 docker containers.

> Oh, look -- here's a *Ready-to-go **complete glftpd install**, including webui:
> <https://github.com/silv3rr/docker-glftpd>*

**2**) If you do not want to use glftpd in docker, use 'local' mode (glftpd-webui itself will still run in container)

**3**) Install webui without using docker at all. Needs nginx with php-fpm8 or newer and requirements below. You'll have to do this manually, have a look at the Dockerfile and adapt to your specific environment

- copy webui files to document root: assets, lib, templates and src dirs to e.g. /var/www/html
- copy config.php.dist to config.php
- copy nginx conf templates from etc
- check 'bin_dir' under 'local' in config.php (default is '/usr/local/bin')
- download/replace pyspy and pywho for your distro in 'bin_dir'
- copy/compile other bins (gotty, hashgen, passchk)
- copy etc/sudoers.d/glftpd-web

### Configuration

Options such as 'webui-mode', 'auth', a custom html title and display settings can be changed in `config.php`.

For details about all settings see [docs/Config.md](docs/Config.md)

### Requirements

- Network: webui needs to be able to connect to tcp ports 1337(glftpd), 3333(bot), 8080(gotty) and 5050(pyspy) 
- User management: needs access to /glftpd dir, either in glftpd container or on same host
- Stop/start glftpd: needs access to docker socket or systemd/service + sudo in local webui-mode
- Terminal commands: these need to run in glftpd container or on same host in local webui-mode, local mode requires php 'exec'
- Filemanager: needs access to config files and /glftpd/site

## Compose

Run pre-made images from github:

`docker compose up --detach`

Build and run local images:

`docker compose --profile full up --build --detach local-web`

(starts both webui and gl containers)

Edit 'docker-compose.yml' to change mounts, ports, vars etc. If you dont want the docker-glftpd container, remove 'glftpd' service.

## Image

- base: latest alpine
- size: ~50mb
- webserver: nginx, php8 fpm
- stand-alone & fully separate image from 'glftpd'
- logs: nginx logs to stderr/stdout
- view access logs with `docker logs glftpd-web`

## Variables

Options can be set with environment variables, or (permanently) in docker-run.sh or docker-compose.yml.

Example:

env var in shell: `WEBUI_AUTH_MODE="both"`

edit docker-run\.sh: `WEBUI_AUTH_MODE="both"`   _(default: "./glftpd")_

docker compose:
```
# add to docker-compose.yml
services:
  web:
    image: ghcr.io/silv3rr/docker-glftpd-web:latest
    # <...>
    environment:
        WEBUI_AUTH_MODE="both"
```

## Troubleshooting

See [docs/Troubleshooting.md](docs/Troubleshooting.md)

## Stack

Cutting-edge tech used:

- PHP, some JQuery and Bootstrap4
- Webserver: nginx and php-fpm
- User management: [gltool.sh](https://github.com/silv3rr/scripts/blob/master/gltool.sh) wrapper (Bash)
- Filemanager: [tinyfilemanager](https://tinyfilemanager.github.io/)
- Web Terminal: [GoTTY](https://github.com/sorenisanerd/gotty)
- Spy: [pyspy](https://github.com/silv3rr/pyspy) (flask)
- Pywho: uses [ansi-escapes-to-html](https://github.com/neilime/ansi-escapes-to-html)

# Screenshots 

_Notification about added gadmin_

![notification](docs/images/notification.png "Notification on top")

_Terminal modal showing bot_

![bot](docs/images/bot.png "Terminal modal showing bot")

> View **[more images](docs/images)**
