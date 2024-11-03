#!/bin/bash

echo "Starting spy in background..."
nohup bash -c "/glftpd/bin/spy --web" </dev/null &>/dev/null &
