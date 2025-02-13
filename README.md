# glftpd-webui (V5)

## /gl wɛb ʊi/

[![Docker](https://github.com/silv3rr/glftpd-webui/actions/workflows/docker.yml/badge.svg)](https://github.com/silv3rr/glftpd-webui/actions/workflows/docker.yml)

Web GUI for [glftpd](https://glftpd.io) (Linux only)

Shows online users, status and stops/starts glftpd. Can also be used to view logs, edit config files and browse site. All user management can be done using forms. Includes a browser terminal that displays gl_spy, useredit and bot partyline (using websockets). It should display fine on mobile devices too.

_Main page UI, click to enlarge_

> [![main](docs/images/webui_small.png "Main page")](docs/images/webui.png)

## Usage

Start: `./docker-run.sh` or `docker run ghcr.io/silv3rr/glftpd-webui`

Open url: https://your.ip:4444 and login: `shit/EatSh1t`  (basic web auth).

## Installation

Pick one of these 3 options

**1**) The easiest and preferred way to use glftpd-webui is to run both glftpd and webui in 2 docker containers.

> _Oh, look -- here's a *Ready-to-go **complete glftpd install**, including webui:_
> <https://github.com/silv3rr/docker-glftpd>

**2**) If you do not want to use glftpd in docker, use 'local' mode (glftpd-webui itself will still run in container)

**3**) Install webui, without using docker at all. Ether try running [local-install.sh](local-install.sh) or [manually](docs/Setup.md)

### Configuration

Options such as 'webui-mode', 'auth', a custom html title and display settings can be changed in `config.php`.

For details about installation and settings see [docs/Setup.md](docs/Setup.md)

Auth and http user/passwd can be changed via webui after logging in.

## Compose

Run pre-made images from github:

`docker compose up --detach`

Build and run local images:

`docker compose --profile full up --build --detach local-web`

(starts both webui and gl containers)

Edit 'docker-compose.yml' to change mounts, ports, vars etc. If you dont want the docker-glftpd container, remove 'glftpd' service.

## Image

- base: latest alpine (_or debian_*)
- size: ~50mb
- webserver: nginx, php8 fpm
- stand-alone & fully separate image from 'glftpd'
- logs: nginx logs to stderr/stdout
- view access logs with `docker logs glftpd-web`

_\* debian image available for using systemd and dbus broker, not needed usually_

## Variables

Options can be set with environment variables, or (permanently) in docker-run.sh or docker-compose.yml.

Example:

as env var in shell: `WEBUI_AUTH_MODE="both"`

or edit docker-run\.sh: `WEBUI_AUTH_MODE="both"`   _(default: "./glftpd")_

or use docker compose:

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

- PHP, some JQuery, Bootstrap4 and fontawesome
- Webserver: nginx and php-fpm
- User management: [gltool.sh](https://github.com/silv3rr/scripts/blob/master/gltool.sh) wrapper (Bash)
- Filemanager: [tinyfilemanager](https://tinyfilemanager.github.io/) (offline branch)
- Web Terminal: [GoTTY](https://github.com/sorenisanerd/gotty)
- Spy: [pyspy](https://github.com/silv3rr/pyspy) (flask)
- Sitewho: [pywho](https://github.com/silv3rr/pywho), [ansi-escapes-to-html](https://github.com/neilime/ansi-escapes-to-html)
- Markdown: [parsedown](https://github.com/erusev/parsedown)
- Auth: [ip-lib](https://github.com/mlocati/ip-lib), _[apr1-md5](https://github.com/whitehat101/apr1-md5)_, _[PHP-Htpasswd](https://github.com/ozanhazer/PHP-Htpasswd)_
- Graphs: [svg-chart-builder](https://github.com/xanpena/svg-chart-builder)

# Screenshots 

_Notification about added gadmin_

> ![notification](docs/images/notification.png "Notification on top")

_Terminal modal showing bot_

> ![bot](docs/images/bot.png "Terminal modal showing bot")

--- 
View **[more images](docs/images)**
