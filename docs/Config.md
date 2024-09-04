# Configuration

## Settings

#### webui-mode

docker

- 'api': `<url>`
- 'glftpd_container' `<name>` (default: glftpd)
- 'bin_dir': `<path>` to binaries (gltool.sh, gotty, pywho etc)

local

- 'runas_user': `<user>` for sudo (default: root)
- 'bin_dir': `<path>` to binaries (gltool.sh, gotty, pywho etc)

#### display

show_more_opts

 - `true` always show all options
 - `false` collapse with link "Show/hide more options"

show_alerts

 - `true` show notifications, `false` no notifications

max_items: `<number>` of users and groups to show (without collapse)

modal

 - 'pywho':
    - `true` show pywho in dialog, `false`  output to bottom of page
 - 'commands':
    - `true` show (terminal) commands in dialog, `false` output to bottom of page

spy

 - 'enabled': `true` show online users, `false` hide online users
 - 'refresh': `true` auto refresh, `false` no refresh

#### connections

services

- ftpd:
    - host: `<hostname or ip>` (default: localhost), port: `<num>` (default: 1337)
- sitebot:
    - host: `<hostname or ip>`  (default: localhost), port: `<num>` (default: 3333)

*(used for UP/DOWN status)*

## Webui-mode

Mode 'docker' is the default setting.

### docker

The glftpd image from [docker-glftpd](https://github.com/silv3rr/docker-glftpd) is used (default). To use a different image change it in `docker-run.sh`. Or, to not run gl at all set `GLFTPD=0` e.g. if you already have your own glftpd container running.

The default basedir for glftpd mounts is './glftpd' (workdir on host), set `GLDIR` to change

Both glftpd and webui use same docker network.

### local

Uses 'localhost' (default) to connect to glftpd, bot, spy and gotty.

In local webui-mode, the quickest way to allow access to '/glftpd' dir is bind mounting inside the webui container or to document root.

## Auth

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

Uses nginx and htpasswd to store user/password.

Docker: set `WEBUI_AUTH_MODE` and `WEBUI_AUTH_USER` and `WEBUI_AUTH_PASS` to change credentials.

Without docker: run `./auth.sh basic <user> <password>`

#### glftpd

Besides gl user/pass, also checks userfile for '1' flag (SITEOP) and compares client ip to src ip/host mask(s) in X-Forwarded-For header.

(tested with docker mode only but should also work without)

#### both

Combines 'basic' and 'glftpd' modes. For basic auth, php pops up an input window and compares username and password to 'http_auth' setting from config.php (instead of nginx/htpasswd). To force browser to relogin, try url http:/xxx@your.ip:4444

Docker: set `WEBUI_AUTH_MODE` and `WEBUI_AUTH_USER` and `WEBUI_AUTH_PASS` to change http auth credentials. Can also be changed in docker-run.sh or docker-compose.yml.

Without docker: run `./auth.sh both <user> <password>`

(tested with docker mode only but should also work without)

#### allow ip

Make sure your client's source ip is whitelisted. Default is 'allow' all private ip ranges (rfc1918). To change, edit etc/nginx/auth.d/allow.conf.template (docker) or allow.conf and restart container or reload nginx.
