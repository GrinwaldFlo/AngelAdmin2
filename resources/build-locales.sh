#!/usr/bin/env bash
# Rebuild all translation files (.po -> .mo) in resources/locales
# Requires gettext (Fedora: sudo dnf install gettext)
set -euo pipefail

if ! command -v msgfmt > /dev/null; then
    echo "msgfmt not found, install gettext (Fedora: sudo dnf install gettext)" >&2
    exit 1
fi

localesDir="$(cd "$(dirname "$0")" && pwd)/locales"

for po in "$localesDir"/*/*.po; do
    mo="${po%.po}.mo"
    echo "Building ${mo#"$localesDir"/}"
    msgfmt -o "$mo" "$po"
done
