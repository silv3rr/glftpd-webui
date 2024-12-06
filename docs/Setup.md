# Installation

## docker

The default and recommended setup is using webui in 'docker' mode. Both webui and glftpd run in separate containers. The docker api is used to execute commands in 'glftpd' container.

## local

This mode runs commands locally, on same host. While glftpd-webui runs in a container, gl can either be installed in the same container or on host.

## manual

To install without docker at all - with both glftpd and webgui running on the same host - you need nginx with php-fpm8+. Glftpd should already be installed in /glftpd.

Try running [local-install.sh](local-install.sh). Some manual steps might still be required.

Or, to set this up completely manually, have a look at the Dockerfile and adapt to your specific environment

- copy webui files to document root: assets, lib, templates and src dirs to e.g. /var/www/html
- copy config.php.dist to config.php (set mode to 'local')
- copy nginx conf templates from etc
- enable local mode and check 'bin_dir' under 'local' in config.php (default is '/usr/local/bin')
- download/replace pyspy and pywho for your distro in 'bin_dir'
- make sure `spy --web` is running on localhost:5000
- copy/compile other bins if needed (gotty, hashgen, passchk)
- copy etc/sudoers.d/glftpd-web and set 'runas' in config 

*it might help to have a look at the Dockerfile, adapt to your specific environment*

### Requirements

- Network: webui needs to be able to connect to tcp ports 1337(glftpd), 3333(bot), 8080(gotty) and 5050(pyspy) 
- User management: needs access to /glftpd dir, either in glftpd container or on same host
- Stop/start glftpd: needs access to docker socket or systemd/service + sudo in local webui-mode
- Terminal commands: these need to run in glftpd container or on same host in local webui-mode, local mode requires php 'exec'
- Filemanager: needs access to config files and /glftpd/site

## Settings

These can be changed in config.php

#### mode

docker

- api: `<url>`
- glftpd_ct_name `<name>` (default: glftpd)
- bin_dir: `<path>` to binaries *

local

- runas: `<sudo cmd>` for sudo (default: '/usr/bin/sudo -n -u root')
- bin_dir: `<path>` to binaries *

_* path to gltool.sh, gotty, pywho etc_

#### display

show_more_opts

 - `true` always show all options
 - `false` collapse with link "Show/hide more options"

show_alerts

 - `true` show notifications, `false` no notifications

max_items: `<number>` of users and groups to show (without collapse)

modal

 - commands:
    - `true` show (terminal) commands in dialog, `false` outputs to bottom of page
 - sitewho:
    - `true` show pywho in dialog, `false`  outputs to bottom of page

spy

 - enabled: `true` show online users, `false` hide online users
 - refresh: `true` auto refresh, `false` no refresh

#### connections

services

- glftpd:
    - host: `<hostname or ip>` (default: localhost), port: `<num>` (default: 1337)
- sitebot:
    - host: `<hostname or ip>`  (default: localhost), port: `<num>` (default: 3333)

*(used for UP/DOWN status)*

#### filemanager paths

filemanager

- docker
    - files  (edit)
        - `<filename>`  `<path>`
    - dirs   (list)
        - `<title>` `<path>`
- local
    - files
        - ''
    - dirs
        - ''

*(if there are no files/dirs defined, filemanager tab is not shown)*

#### ui buttons

ui_buttons

- glftpd
    - `<name>` `<cmd>`
- docker
    - ''
- term
    - ''

*(if there are no buttons defined, tab is not shown)*

## Details webui-mode

### docker

Commands: docker_commands.php, uses docker api

Network: both glftpd and webui use same docker network ('shit')

Storage: Default basedir for glftpd mounts is './glftpd' which is the workdir on host, set `GL_DATA` to change

Notes: 

- the 'glftpd' image from [docker-glftpd](https://github.com/silv3rr/docker-glftpd) is used by default
- to use a different image, change it in `docker-run.sh`
- to not start gl at all set `GLFTPD=0`, for example if you already have your own glftpd container running

### local

Enabled if `WEBUI_LOCAL=1` is set on runtime

Commands: local_commands.php, uses php exec

Network: docker host network is used (`--network=host`) and 'localhost' to connect to glftpd, bot, spy and gotty (default)

Storage: to allow access to '/glftpd' dir (`GL_DIR`), it's bind mounted inside the webui container (for non docker you could change this to document root)

Notes:
 
- running webgui in docker but not gl limits what webui can do
- it's possible start/stop glftpd with system and dbus broker, but probably not worth the effort (build the debian image if you want)
- when using manual setup without docker, make sure webui can run commands using sudo and/or systemd

## Auth details

Docker: set `WEBUI_AUTH_MODE=<mode>`

Without docker: run `./auth.sh <mode>`

_auth.sh makes any needed changes to nginx config and config.php and is be triggered by env var or run directly_

### Modes

- basic: http authentication with standard browser dialog to login (default)
- glftpd: login with html user/pass form, checked against glftpd's userdb using `passchk` bin
- both: combines glftpd and basic auth modes
- none: disable auth
- additionally, access is always limited to allowed ip ranges

#### basic

Uses nginx and htpasswd to store user/password (in /etc/nginx/.htpasswd).

Docker: set `WEBUI_AUTH_MODE` and `WEBUI_AUTH_USER` and `WEBUI_AUTH_PASS` to change credentials.

Without docker, run `./auth.sh basic <user> <password>`

#### glftpd

Besides gl user/pass, also checks userfile for '+1' flag (SITEOP) and compares addip/host mask(s) to client's src ip (X-Forwarded-For header).

#### both

Combines 'basic' and 'glftpd' modes. For basic auth, php handles browser input window and compares `username` and password to `'http_auth'` setting from config.php (**instead** of nginx/htpasswd). To force browser to relogin, try url http:/xxx@your.ip:4444

Docker: set `WEBUI_AUTH_MODE` and `WEBUI_AUTH_USER` and `WEBUI_AUTH_PASS` to change http auth credentials. Can also be changed in docker-run.sh or docker-compose.yml.

Without docker: run `./auth.sh both <user> <password>`

#### none

Disables auth (still checks ip)

#### allow ip

Make sure your client's source ip is whitelisted. Default is 'allow' all private ip ranges (rfc1918). To change, edit etc/nginx/auth.d/allow.conf.template (docker) or allow.conf and restart container or reload nginx.
