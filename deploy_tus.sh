#!/usr/bin/env bash
# deploy_tus.sh — sube archivos de crm-tips a crmtrato.com (Hostinger) vía protocolo TUS.
#
# Uso:
#   bash proyectos/crm-tips/deploy_tus.sh <tus_url> <auth_key> <rest_auth_key> <archivo> [archivo...]
#
# <tus_url>, <auth_key>, <rest_auth_key> salen de la herramienta MCP
#   hosting_generateUploadURLV1 (para el usuario y dominio del hosting de destino).
# Los <archivo> son rutas relativas a proyectos/crm-tips/ (ej: includes/header.php,
#   assets/css/style.css). Se preservan como ruta destino dentro de public_html.
#
# NO subir: config.php, config.production.php, CLAUDE.md, setup.php, update_*.php, este script.

set -euo pipefail

if [ "$#" -lt 4 ]; then
  echo "uso: bash deploy_tus.sh <tus_url> <auth_key> <rest_auth_key> <archivo> [archivo...]" >&2
  exit 2
fi

URL="$1"; AK="$2"; RK="$3"; shift 3
cd "$(dirname "$0")"

rc=0
for F in "$@"; do
  if [ ! -f "$F" ]; then
    printf "%-32s  NO EXISTE\n" "$F" >&2
    rc=1
    continue
  fi
  S=$(stat -c%s "$F")
  P1=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$URL/$F?override=true" \
    -H "X-Auth: $AK" -H "X-Auth-Rest: $RK" -H "Tus-Resumable: 1.0.0" \
    -H "Upload-Length: $S" -H "Upload-Offset: 0")
  P2=$(curl -s -o /dev/null -w "%{http_code}" -X PATCH "$URL/$F?override=true" \
    -H "X-Auth: $AK" -H "X-Auth-Rest: $RK" -H "Tus-Resumable: 1.0.0" \
    -H "Content-Type: application/offset+octet-stream" -H "Upload-Offset: 0" \
    --data-binary "@$F")
  printf "%-32s %8d bytes  POST=%s PATCH=%s\n" "$F" "$S" "$P1" "$P2"
  [ "$P1" = "201" ] && [ "$P2" = "204" ] || rc=1
done
exit $rc
