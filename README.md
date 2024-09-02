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


#### webui-mode

'docker':
- 'api': `<url>`
- 'glftpd_container' `<name>` (default: glftpd)
- 'bin_dir': `<path>` to gltool.sh, gotty, pywho etc

'local':
- 'runas_user': `<user>` for sudo (default: root)
- 'bin_dir': `<path>` to gltool.sh, gotty, pywho etc

'services:'
- ftpd:
    - host: `<hostname or ip>` (default: localhost)
    - port: `<port>` (default: 1337)
- sitebot:
    - host: `<hostname or ip>`  (default: localhost)
    - port: `<port>` (default: 3333)

used for UP/DOWN status

##### docker

By default the glftpd image from [docker-glftpd](https://github.com/silv3rr/docker-glftpd) is used. To use a different image change it in `docker-run.sh`, or to not run gl at all set `GLFTPD=0`.

The default basedir on host for glftpd mounts is './glftpd', set `GLDIR` to change

Both glftpd and webui use same docker network.

##### local

By default localhost is used to connect to glftpd, bot, spy and gotty.

The quickest way to allow access to '/glftpd' dir is bind mounting in the webui container or document root.

#### auth

Docker: set `WEBUI_AUTH_MODE=<mode>`

Without docker: run `./auth.sh <mode>`

_auth.sh makes any needed changes to nginx config and config.php and is be triggered by env var or run directly_

auth modes:

- basic: http authentication with standard browser dialog to login (default)
- glftpd: login with html user/pass form, checked against glftpd's userdb using `passchk` bin
- both: combines glftpd and basic auth modes
- none: disable auth

additionally access is always limited to allowed ip ranges

##### basic 

Uses nginx and htpasswd to store user/password.

Docker: set `WEBUI_AUTH_MODE` and `WEBUI_AUTH_USER` and `WEBUI_AUTH_PASS` to change credentials.

Without docker: run `./auth.sh basic <user> <password>`

##### glftpd

Besides gl user/pass, also checks userfile for '1' flag (SITEOP) and compares client ip to src ip/host mask(s) in X-Forwarded-For header.

(tested with mode only but should also work witout)

##### both

Combines 'basic' and 'glftpd' modes. For basic auth, php pops up an input window and compares username and password to 'http_auth' setting from config.php (instead of nginx/htpasswd). To force browser to relogin, try url http:/xxx@your.ip:4444

Docker: set `WEBUI_AUTH_MODE` and `WEBUI_AUTH_USER` and `WEBUI_AUTH_PASS` to change http auth credentials. Can also be changed in docker-run.sh or docker-compose.yml.

Without docker: run `./auth.sh both <user> <password>`

(tested with mode only but should also work witout)

##### allow ip

Make sure your client's source ip is whitelisted. Default is 'allow' all private ip ranges (rfc1918). To change, edit etc/nginx/auth.d/allow.conf.template (docker) or allow.conf and restart container or reload nginx.

#### display settings

'show_more_opts':
 - `true` always show all options
 - `false` collapse with link "Show/hide more options"

'show_alerts':
 - `true` show notifications
 - `false` no notifications

'max_items': `<number>` of users and groups to show (without collapse)

'modal':
 - 'pywho':
    - `true` show pywho in dialog, `false`  output to bottom of page
 - 'commands':
    - `true` show (terminal) commands in dialog, `false` output to bottom of page

'spy':
 - 'enabled':
    - `true` show online users
    - `false` hide online users
 - 'refresh':
    - `true` auto refresh
    - `false` no refresh

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

- "nothing happens", main page doesnt open, cant login etc
    - make sure glftpd is accessible (container runs)
    - try disabling auth

- x doesnt work
    - start with checking nginx error log for (php) errors, in docker mode: `docker logs glftpd-webui`
    - make sure error_log in nginx config is set to: `error_log  /var/log/nginx/error.log debug;`
     
- docker (api) doesnt work
    - set `debug` in config to 3, test an action and check /tmp/curl_err.log

- user mgmt errors
    - test running `gltool.sh` manually,  e.g. `bin/gltool.sh -c RAWUSERFILE -u test`

- status incorrectly shows glftpd/bot down 
    - check ports in `services` option in config.php

- i want to add a new button to run a command
    - sure, good luck with that :P ... 
    - ok, ok. to get you started: edit main html template and {docker,local}_commands.php

-  why is this using docker / stupid php / not react / todays js framework / not properly written OOP code 
    - coz of ur mom

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
