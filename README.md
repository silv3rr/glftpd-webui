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

**3**) Install webui without using docker at all. Needs nginx with php-fpm8 or newer and requirements below. You'll have to do this manually, have a look at the Dockerfile and adapt to your specific environment.

- copy webui files to document root: assets, lib, templates and src dirs e.g. to /var/www/html
- copy nginx conf templates from etc
- check 'bin_dir' under 'local' in config.php (default is '/usr/local/bin')
- download/replace pyspy and pywho for your distro to 'bin_dir'
- copy/compile other bins (gotty, hashgen, passchk)
- copy etc/sudoers.d/glftpd-web

### Configuration

Options such as webui-mode ('docker' or 'local'), 'auth', a custom html title and display settings can be changed in `config.php`.

#### auth

Set `WEBUI_AUTH_USER` or run `./auth.sh <mode>`

auth modes:

- basic: http authentication with standard browser pop up to login (default)
- glftpd: login with html user/pass form, checked against glftpd's userdb using `passchk` bin
- both: enables glftpd and basic at the same time
- none: disable auth
- additionally access is always limited to allowed ip ranges (see 'nginx' below)

##### basic 

Uses nginx and htpasswd to store user/password.

In webui-mode docker mode, set `WEBUI_AUTH_USER` and `WEBUI_AUTH_PASS` to change credentials. This can also be changed in docker-run.sh or docker-compose.yml.

In local mode, run: `./auth.sh basic <user> <password>`

##### glftpd

Besides user/pass, also checks userfile for flag '1'(SITEOP) and compares client ip to ip/host mask(s) in X-Forwarded-For header.

(tested in 'docker' mode only but should also work for 'local')

##### both

Combines 'glftpd' and 'basic'. To make this work for basic auth, php pops up an input window and compares user/pass to config.php (instead of nginx/htpasswd). Change http auth credentials the same way as 'basic'. To force browser to relogin, try url http:/xxx@your.ip:4444

(tested in 'docker' mode only but should also work for 'local')

#### webui-modes

docker mode:

- by default, the glftpd image from https://github.com/silv3rr/docker-glftpd is used
- to use a different image:
    - change image in docker-run.sh, or dont run image at all: `GLFTPD=0`
    - for compose: edit/remove services in docker-compose.yml
- the default container name is "glftpd", to change set `'glftpd_container'` under 'docker' in config.php
- the default basedir on host for glftpd mounts is './glftpd', set `GLDIR` to change
- glftpd and webgui use same docker network

local mode:

- by default, localhost is used to connect to gl/bot/spy/gotty
- the quickest way to allow access to '/glftpd' dir is bind mounting in the webui container or document root

#### nginx

Make sure your client's source ip is whitelisted. Default is 'allow' all private ip ranges (rfc1918). To change, edit etc/nginx/auth.d/allow.conf and restart.

### Requirements

- Network: webui need to be able to connect to tcp ports 1337(glftpd), 3333(bot), 8080(gotty) and 5050(pyspy) 
- User management: needs access to /glftpd dir, either in glftpd container or on same host
- Stop/start glftpd: needs access to docker socket or systemd/service + sudo in local mode
- Terminal commands: these need to run in glftpd container or on same host in local mode (php exec)
- Filemanager: needs access to config files and /glftpd/site

## Image

- base: latest alpine
- size: ~50mb
- webserver: nginx, php8 fpm
- stand-alone & fully separate image from 'glftpd'
- logs: nginx logs to stderr/stdout
- view access logs with `docker logs glftpd-web`

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
    - ok.. ok. to get you started: edit main html template and {docker,local}_commands.php

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
