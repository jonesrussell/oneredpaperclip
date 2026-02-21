#!/usr/bin/env bash
set -e
cd "$(dirname "$0")"
exec vendor/bin/dep deploy coforge.xyz "$@"
