# Config Settings

### mode

docker

- api: `<url>`
- glftpd_ct_name `<name>` (default: glftpd)
- bin_dir: `<path>` to binaries  *

local

- runas: `<sudo cmd>` for sudo (default: '/usr/bin/sudo -n -u root')
- bin_dir: `<path>` to binaries

_* path to gltool.sh, gotty, pywho etc_


### display

show_alerts

 - `true` show notification
 - `false` no notifications

show_more_options

 - `true` always show all options  (default)
 - `false` collapse with link "Show/hide more options"

max_items: `<number>` of users and groups to show (without collapse)

modal

 - commands:
    - `true` show (terminal) commands in dialog, `false` outputs to bottom of page
 - sitewho:
    - `true` show pywho in dialog, `false`  outputs to bottom of page
 - userstats:
    - `true` show userstats in dialog, `false`  outputs to bottom of page

spy

 - show: `true` show online users, `false` hide online users
 - refresh: `true` auto refresh, `false` no refresh

theme

- title: `<html>`

- button colors *
    - btn-color-1: `<primary|secondary|info|...>` and `<custom|purple|gray>`
    - btn-color-2: `<...>`
    - btn-small-color: `<...>`
    
*\* for theme button colors see https://getbootstrap.com/docs/4.0/components/buttons/*
\* *plus these extra colors: 'custom', 'purple', 'gray'*


### connections

services

- glftpd:
    - host: `<hostname|ip>` (default: localhost), port: `<num>` (default: 1337)
- sitebot:
    - host: `<hostname|ip>`  (default: localhost), port: `<num>` (default: 3333)

*(used for UP/DOWN status)*

### filemanager

filemanager  *

- (Browse) Name:
    - type: 'dir', path: "/path/to/dir"
- (Edit/View) filename:
    - type: 'file', mode: `<edit|view>`, path: "/path/to/filename/"

default file mode is 'edit'

if there are no dirs/files defined, filemanager tab is not shown

_* if path is empty, defaults for mode is used (docker/local)_

### ui buttons

buttons

- Glftpd
    - name: `<cmd>`
- Docker
    - name: `<cmd>`
- Terminal
    - name: `<cmd>`

add separator(s) with `'sep'`

*(if there are no buttons defined, it's tab is not shown)*

### stats

- commands
    - cmd: `<cmd_name>`, stat: `<stat_name>`, show: `<0=hide|1=stats-page|2=main>`
- options

- palette
    - name
        - `<#color-1>`
        - `<#color-2>`
        - `<#color-3>`
