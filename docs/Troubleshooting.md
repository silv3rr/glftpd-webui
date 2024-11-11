# Troubleshooting

- "nothing happens", main page doesnt open, cant login etc
    - make sure glftpd is accessible (container runs)
    - try disabling auth

- x doesnt work
    - start with checking nginx error log for (php) errors, in docker mode: `docker logs glftpd-webui`
    - make sure error_log in nginx config is set to: `error_log  /var/log/nginx/error.log debug;`
     
- docker (api) doesnt work
    - set `debug` in config to 3, test an action and check /tmp/curl_err.log

- user mgmt errors
    - test running `gltool.sh` manually,  e.g. `bin/gltool.sh -c RAWUSERFILE -u testuser`

- status incorrectly shows glftpd/bot down 
    - check ports in `services` option in config.php

- i can't get manual setup option (3) to work
    - it does require more effort to get working, maybe you should give up and try docker.. ;x

- i want to add a new button to run a command
    - sure, good luck with that :P ... ok, ok. to get you started: edit main html template and {docker,local}_commands.php

-  why is this using docker / stupid php / not react / todays js framework / not properly written OOP code 
    - coz of ur mom
