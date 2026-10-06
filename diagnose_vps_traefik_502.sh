#!/usr/bin/env bash
# ==============================================================================
# SCRIPT FORENSE Y DE DIAGNÓSTICO PROFUNDO: TRAEFIK 502 BAD GATEWAY EN REY BINGO
# Ejecutar directamente en la terminal SSH del VPS (179.236.225.203) como root:
#   bash diagnose_vps_traefik_502.sh
# ==============================================================================

set -o pipefail

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}==============================================================================${NC}"
echo -e "${YELLOW}  DIAGNÓSTICO FORENSE DE PRODUCCIÓN - VPS DOKPLOY / TRAEFIK 502${NC}"
echo -e "  Host: $(hostname) | IP: $(hostname -I | awk '{print $1}') | Fecha: $(date)"
echo -e "${CYAN}==============================================================================${NC}\n"

# 1. IDENTIFICAR CONTENEDORES (REY BINGO Y TRAEFIK)
echo -e "${GREEN}[1] IDENTIFICACIÓN DE CONTENEDORES EN DOCKER${NC}"
docker ps -a --format "table {{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"

# Buscar contenedor de Rey Bingo
BINGO_CID=$(docker ps -a --filter "name=reybingo" --format "{{.ID}}" | head -n 1)
if [ -z "$BINGO_CID" ]; then
    BINGO_CID=$(docker ps -a --filter "name=bingo" --format "{{.ID}}" | head -n 1)
fi
if [ -z "$BINGO_CID" ]; then
    # Buscar por imagen o Dokploy pattern
    BINGO_CID=$(docker ps -a | grep -iE "rey|bingo" | awk '{print $1}' | head -n 1)
fi

# Buscar contenedor de Traefik (Dokploy suele llamarlo dokploy-traefik o traefik)
TRAEFIK_CID=$(docker ps -a --filter "name=traefik" --format "{{.ID}}" | head -n 1)

echo -e "\n  Contenedor Rey Bingo Detectado : ${YELLOW}${BINGO_CID:-NO ENCONTRADO}${NC}"
echo -e "  Contenedor Traefik Detectado   : ${YELLOW}${TRAEFIK_CID:-NO ENCONTRADO}${NC}\n"

if [ -z "$BINGO_CID" ]; then
    echo -e "${RED}[ERROR CRÍTICO] No se encontró ningún contenedor relacionado con Rey Bingo.${NC}"
    echo -e "Listado completo de contenedores:"
    docker ps -a
    exit 1
fi

BINGO_NAME=$(docker inspect --format '{{.Name}}' "$BINGO_CID" | sed 's/^\///')

# 2. ESTADO DETALLADO DEL CONTENEDOR REY BINGO
echo -e "${GREEN}[2] ESTADO DE EJECUCIÓN (docker inspect)${NC}"
STATUS=$(docker inspect --format '{{.State.Status}}' "$BINGO_CID")
RUNNING=$(docker inspect --format '{{.State.Running}}' "$BINGO_CID")
RESTARTING=$(docker inspect --format '{{.State.Restarting}}' "$BINGO_CID")
EXITCODE=$(docker inspect --format '{{.State.ExitCode}}' "$BINGO_CID")
ERROR_MSG=$(docker inspect --format '{{.State.Error}}' "$BINGO_CID")
OOM_KILLED=$(docker inspect --format '{{.State.OOMKilled}}' "$BINGO_CID")
RESTART_COUNT=$(docker inspect --format '{{.RestartCount}}' "$BINGO_CID")
STARTED_AT=$(docker inspect --format '{{.State.StartedAt}}' "$BINGO_CID")
FINISHED_AT=$(docker inspect --format '{{.State.FinishedAt}}' "$BINGO_CID")

echo -e "  Nombre          : $BINGO_NAME"
echo -e "  Status          : $([ "$STATUS" == "running" ] && echo -e "${GREEN}$STATUS${NC}" || echo -e "${RED}$STATUS${NC}")"
echo -e "  Running         : $RUNNING"
echo -e "  Restarting      : $([ "$RESTARTING" == "true" ] && echo -e "${RED}SI (RESTART LOOP)${NC}" || echo "false")"
echo -e "  ExitCode        : $([ "$EXITCODE" == "0" ] && echo -e "${GREEN}0${NC}" || echo -e "${RED}$EXITCODE${NC}")"
echo -e "  OOMKilled       : $([ "$OOM_KILLED" == "true" ] && echo -e "${RED}SI (MATADO POR FALTA DE RAM)${NC}" || echo "false")"
echo -e "  RestartCount    : $RESTART_COUNT"
echo -e "  Error           : ${ERROR_MSG:-Ninguno}"
echo -e "  StartedAt       : $STARTED_AT"
echo -e "  FinishedAt      : $FINISHED_AT"

# 3. COMPROBACIÓN DE HEALTHCHECK
echo -e "\n${GREEN}[3] HEALTHCHECK${NC}"
HEALTH_STATUS=$(docker inspect --format '{{json .State.Health}}' "$BINGO_CID" 2>/dev/null || echo "null")
if [ "$HEALTH_STATUS" != "null" ] && [ -n "$HEALTH_STATUS" ]; then
    echo -e "  Health JSON     : $HEALTH_STATUS"
else
    echo -e "  HealthCheck     : No configurado en Dockerfile / Dokploy"
fi

# 4. PUERTOS Y LABELS DE TRAEFIK
echo -e "\n${GREEN}[4] PUERTOS Y LABELS DE TRAEFIK EN EL CONTENEDOR${NC}"
echo -e "  -- ExposedPorts (Docker) --"
docker inspect --format '{{json .Config.ExposedPorts}}' "$BINGO_CID"

echo -e "\n  -- PortBindings (Host) --"
docker inspect --format '{{json .HostConfig.PortBindings}}' "$BINGO_CID"

echo -e "\n  -- Labels de Traefik --"
docker inspect --format '{{json .Config.Labels}}' "$BINGO_CID" | tr ',' '\n' | grep -i "traefik" || echo "  (Sin labels traefik directos en este contenedor)"

# Extraer el puerto exacto configurado para el load balancer de Traefik
TRAEFIK_PORT=$(docker inspect --format '{{json .Config.Labels}}' "$BINGO_CID" | grep -o 'traefik\.http\.services\.[^.]*\.loadbalancer\.server\.port[^"]*' | head -n 1)
echo -e "  --> Puerto de destino en Traefik: ${YELLOW}${TRAEFIK_PORT:-NO DETECTADO EN LABELS}${NC}"

# 5. REDES DOCKER (NETWORK MATCH)
echo -e "\n${GREEN}[5] REDES DOCKER Y CONECTIVIDAD${NC}"
BINGO_NETWORKS=$(docker inspect --format '{{json .NetworkSettings.Networks}}' "$BINGO_CID" | grep -o '"[^"]*":{' | tr -d '":{')
BINGO_IP=$(docker inspect --format '{{range .NetworkSettings.Networks}}{{.IPAddress}} {{end}}' "$BINGO_CID" | awk '{print $1}')

echo -e "  Redes de Rey Bingo : $BINGO_NETWORKS"
echo -e "  IP interna Bingo   : ${YELLOW}$BINGO_IP${NC}"

if [ -n "$TRAEFIK_CID" ]; then
    TRAEFIK_NETWORKS=$(docker inspect --format '{{json .NetworkSettings.Networks}}' "$TRAEFIK_CID" | grep -o '"[^"]*":{' | tr -d '":{')
    TRAEFIK_IP=$(docker inspect --format '{{range .NetworkSettings.Networks}}{{.IPAddress}} {{end}}' "$TRAEFIK_CID" | awk '{print $1}')
    echo -e "  Redes de Traefik   : $TRAEFIK_NETWORKS"
    echo -e "  IP interna Traefik : $TRAEFIK_IP"

    # Verificar si comparten red
    SHARED_NET=""
    for bn in $BINGO_NETWORKS; do
        for tn in $TRAEFIK_NETWORKS; do
            if [ "$bn" == "$tn" ]; then
                SHARED_NET="$bn"
                break 2
            fi
        done
    done

    if [ -n "$SHARED_NET" ]; then
        echo -e "  --> Red compartida : ${GREEN}SI ($SHARED_NET)${NC}"
    else
        echo -e "  --> Red compartida : ${RED}NO! Traefik y Rey Bingo NO están en la misma red Docker. (ESTA ES CAUSA DIRECTA DE 502)${NC}"
    fi
fi

# 6. LOGS RECIENTES DE REY BINGO (Últimas 100 líneas)
echo -e "\n${GREEN}[6] LOGS RECIENTES DEL CONTENEDOR REY BINGO (docker logs)${NC}"
docker logs --tail 80 "$BINGO_CID"

# 7. PROBANDO PROCESOS Y PUERTOS INTERNOS DENTRO DEL CONTENEDOR
echo -e "\n${GREEN}[7] INSPECCIÓN INTERNA DENTRO DEL CONTENEDOR (Si está running)${NC}"
if [ "$STATUS" == "running" ]; then
    echo -e "  -- Procesos activos (ps aux) --"
    docker exec "$BINGO_CID" ps aux || echo "No se pudo ejecutar ps aux"

    echo -e "\n  -- Puertos a la escucha dentro del contenedor --"
    docker exec "$BINGO_CID" ss -lntp 2>/dev/null || docker exec "$BINGO_CID" netstat -lntp 2>/dev/null || echo "ss/netstat no disponible"

    echo -e "\n  -- Sintaxis de Apache (apachectl -t) --"
    docker exec "$BINGO_CID" apachectl -t 2>/dev/null || echo "apachectl falló"

    echo -e "\n  -- VirtualHosts de Apache (apachectl -S) --"
    docker exec "$BINGO_CID" apachectl -S 2>/dev/null || echo "apachectl -S falló"

    echo -e "\n  -- Prueba HTTP interna a 127.0.0.1:80 --"
    docker exec "$BINGO_CID" curl -I -s -m 5 "http://127.0.0.1:80/" || echo -e "${RED}Fallo al conectar a http://127.0.0.1:80 dentro del contenedor${NC}"
else
    echo -e "  ${RED}El contenedor NO está running ($STATUS). No se puede inspeccionar con docker exec.${NC}"
fi

# 8. PRUEBA DIRECTA DESDE TRAEFIK HACIA REY BINGO
echo -e "\n${GREEN}[8] PRUEBA DE CONEXIÓN DESDE EL CONTENEDOR DE TRAEFIK${NC}"
if [ -n "$TRAEFIK_CID" ] && [ -n "$BINGO_IP" ]; then
    echo -e "  Intentando conectar desde Traefik hacia $BINGO_IP:80..."
    docker exec "$TRAEFIK_CID" wget -q -O - --timeout=3 "http://$BINGO_IP:80/" >/dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo -e "  --> Conexión Traefik -> $BINGO_IP:80 : ${GREEN}EXITOSA (HTTP 200/301/302)${NC}"
    else
        echo -e "  Intentando con curl / nc / wget..."
        docker exec "$TRAEFIK_CID" sh -c "wget -S --spider --timeout=3 http://$BINGO_IP:80 2>&1" || \
        docker exec "$TRAEFIK_CID" sh -c "nc -z -w 3 $BINGO_IP 80 && echo 'Puerto 80 abierto' || echo 'Conexión rechazada/timeout'"
    fi
else
    echo -e "  No se pudo realizar la prueba directa Traefik -> Bingo (falta container ID o IP)."
fi

# 9. LOGS DE TRAEFIK (Búsqueda de 502 / Bad Gateway)
echo -e "\n${GREEN}[9] LOGS DE TRAEFIK (Búsqueda de errores con reybingo.com)${NC}"
if [ -n "$TRAEFIK_CID" ]; then
    docker logs --tail 150 "$TRAEFIK_CID" 2>&1 | grep -iE "reybingo|502|connection refused|dial tcp|bad gateway|no route" | tail -n 25 || echo "Sin errores específicos en últimas 150 líneas"
else
    echo "Contenedor de Traefik no identificado."
fi

# 10. ESTADO DE MEMORIA DEL VPS
echo -e "\n${GREEN}[10] MEMORIA Y RECURSOS DEL SERVIDOR VPS${NC}"
free -h
echo -e "\nUptime y Carga:"
uptime

echo -e "\n${CYAN}==============================================================================${NC}"
echo -e "${YELLOW}  FIN DEL DIAGNÓSTICO FORENSE${NC}"
echo -e "${CYAN}==============================================================================${NC}\n"
