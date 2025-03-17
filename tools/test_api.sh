#!/bin/bash

# Colores para los mensajes
GREEN="\033[0;32m"
YELLOW="\033[1;33m"
CYAN="\033[0;36m"
RED="\033[0;31m"
BLUE="\033[0;34m"
PURPLE="\033[0;35m"
RESET="\033[0m"

# Variables por defecto
HOST="localhost"
PORT="${1:-8080}"
BASE_URL="http://$HOST:$PORT/api"
TOKEN_FILE="/tmp/fungi_api_token.txt"
USER_FILE="/tmp/fungi_api_user.txt"
DEFAULT_LANG="es" # Idioma por defecto

# Banner del script
show_banner() {
    echo -e "${CYAN}════════════════════════════════════════════════════════════════════${RESET}"
    echo -e "                ${GREEN}FUNGI API TESTER${RESET} - Puerto: ${YELLOW}$PORT${RESET}"
    echo -e "${CYAN}════════════════════════════════════════════════════════════════════${RESET}"
}

# Función para mostrar el menú principal
show_menu() {
    echo -e "\n${YELLOW}Opciones disponibles:${RESET}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
    echo -e "${YELLOW}AUTENTICACIÓN${RESET}"
    echo -e "  ${YELLOW}1.${RESET} Registrar nuevo usuario"
    echo -e "  ${YELLOW}2.${RESET} Iniciar sesión (obtener token JWT)"
    echo -e "  ${YELLOW}3.${RESET} Ver estado actual (usuario y token)"
    echo -e "${YELLOW}HONGOS - LECTURA${RESET}"
    echo -e "  ${YELLOW}4.${RESET} Listar todos los hongos"
    echo -e "  ${YELLOW}5.${RESET} Obtener hongo por ID"
    echo -e "  ${YELLOW}6.${RESET} Obtener hongo aleatorio"
    echo -e "  ${YELLOW}7.${RESET} Buscar hongos por campo"
    echo -e "  ${YELLOW}8.${RESET} Listar hongos con paginación"
    echo -e "${YELLOW}HONGOS - ESCRITURA (REQUIERE ADMIN)${RESET}"
    echo -e "  ${YELLOW}9.${RESET} Crear nuevo hongo"
    echo -e "  ${YELLOW}10.${RESET} Actualizar hongo existente"
    echo -e "  ${YELLOW}11.${RESET} Eliminar hongo"
    echo -e "${YELLOW}USUARIO${RESET}"
    echo -e "  ${YELLOW}12.${RESET} Ver perfil"
    echo -e "  ${YELLOW}13.${RESET} Marcar hongo como favorito"
    echo -e "  ${YELLOW}14.${RESET} Dar like a un hongo"
    echo -e "  ${YELLOW}15.${RESET} Ver mis hongos favoritos"
    echo -e "${YELLOW}CONFIGURACIÓN${RESET}"
    echo -e "  ${YELLOW}16.${RESET} Cambiar idioma (actual: ${CURRENT_LANG:-$DEFAULT_LANG})"
    echo -e "  ${YELLOW}0.${RESET} Salir"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
}

# Función para comprobar si existe un token JWT
check_token() {
    if [ ! -f "$TOKEN_FILE" ]; then
        echo -e "${RED}No hay token JWT. Inicia sesión primero (opción 2)${RESET}"
        return 1
    fi
    JWT_TOKEN=$(cat "$TOKEN_FILE")
    return 0
}

# Función para formatear la salida JSON
format_json() {
    if command -v jq &> /dev/null; then
        jq '.'
    else
        cat
    fi
}

# Función para seleccionar el idioma
select_language() {
    echo -e "${YELLOW}Seleccionar idioma:${RESET}"
    echo -e "1. Español (es)"
    echo -e "2. English (en)"
    echo -e "3. Català (ca)"
    read -p "Selecciona un idioma [1-3]: " lang_option
    
    case $lang_option in
        1) CURRENT_LANG="es" ;;
        2) CURRENT_LANG="en" ;;
        3) CURRENT_LANG="ca" ;;
        *) echo -e "${RED}Opción no válida. Usando idioma por defecto (es).${RESET}"; CURRENT_LANG="es" ;;
    esac
    
    echo -e "${GREEN}Idioma cambiado a: ${CURRENT_LANG}${RESET}"
}

# Registrar un nuevo usuario
register_user() {
    echo -e "${YELLOW}Registrando nuevo usuario...${RESET}"
    read -p "Nombre de usuario: " username
    read -p "Email: " email
    read -s -p "Contraseña: " password
    echo ""
    
    response=$(curl -s -X POST "$BASE_URL/users" \
        -H "Content-Type: application/json" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}" \
        -d "{\"username\":\"$username\",\"email\":\"$email\",\"password\":\"$password\"}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
    
    # Guardar usuario para futuras referencias
    echo "$username" > "$USER_FILE"
    echo -e "${CYAN}Usuario guardado para referencia.${RESET}"
}

# Iniciar sesión
login() {
    echo -e "${YELLOW}Iniciando sesión...${RESET}"
    
    # Si hay un usuario guardado, sugerirlo
    if [ -f "$USER_FILE" ]; then
        DEFAULT_USER=$(cat "$USER_FILE")
        read -p "Nombre de usuario [$DEFAULT_USER]: " username
        username=${username:-$DEFAULT_USER}
    else
        read -p "Nombre de usuario: " username
    fi
    
    read -s -p "Contraseña: " password
    echo ""
    
    response=$(curl -s -X POST "$BASE_URL/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}" \
        -d "{\"username\":\"$username\",\"password\":\"$password\"}")
    
    # Extraer el token JWT y guardarlo si la autenticación fue exitosa
    if echo "$response" | grep -q "\"success\":true"; then
        token=$(echo "$response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
        echo "$token" > "$TOKEN_FILE"
        echo "$username" > "$USER_FILE"
        echo -e "${GREEN}Sesión iniciada correctamente. Token JWT guardado.${RESET}"
    else
        echo -e "${RED}Error al iniciar sesión.${RESET}"
    fi
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Verificar estado actual
check_status() {
    echo -e "${YELLOW}Estado actual:${RESET}"
    
    if [ -f "$USER_FILE" ]; then
        USER=$(cat "$USER_FILE")
        echo -e "${GREEN}Usuario:${RESET} $USER"
    else
        echo -e "${RED}No hay usuario guardado.${RESET}"
    fi
    
    if [ -f "$TOKEN_FILE" ]; then
        TOKEN=$(cat "$TOKEN_FILE")
        TRUNCATED_TOKEN="${TOKEN:0:20}...${TOKEN: -20}"
        echo -e "${GREEN}Token:${RESET} $TRUNCATED_TOKEN"
        
        # Verificar si el token es válido
        response=$(curl -s -X GET "$BASE_URL/users/profile" \
            -H "Authorization: Bearer $TOKEN" \
            -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
        
        if echo "$response" | grep -q "\"success\":true"; then
            echo -e "${GREEN}Token válido${RESET}"
            
            # Intentar determinar si es admin
            if echo "$response" | grep -q "\"role\":\"admin\""; then
                echo -e "${PURPLE}¡Usuario con privilegios de administrador!${RESET}"
            else
                echo -e "${BLUE}Usuario regular${RESET}"
            fi
        else
            echo -e "${RED}Token inválido o expirado${RESET}"
        fi
    else
        echo -e "${RED}No hay token JWT guardado.${RESET}"
    fi
}

# Listar todos los hongos
list_fungi() {
    echo -e "${YELLOW}Obteniendo lista de hongos...${RESET}"
    
    response=$(curl -s -X GET "$BASE_URL/fungi" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Obtener hongo por ID
get_fungus_by_id() {
    echo -e "${YELLOW}Obteniendo hongo por ID...${RESET}"
    read -p "ID del hongo: " id
    
    response=$(curl -s -X GET "$BASE_URL/fungi/$id" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Obtener hongo aleatorio
get_random_fungus() {
    echo -e "${YELLOW}Obteniendo hongo aleatorio...${RESET}"
    
    response=$(curl -s -X GET "$BASE_URL/fungi/random" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Buscar hongos por campo
search_fungi_by_field() {
    echo -e "${YELLOW}Buscar hongos por campo...${RESET}"
    echo -e "Campos disponibles: name, common_name, edibility, habitat, etc."
    read -p "Campo: " field
    read -p "Valor: " value
    
    response=$(curl -s -X GET "$BASE_URL/fungi/search/$field/$value" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Listar hongos con paginación
list_fungi_paginated() {
    echo -e "${YELLOW}Obteniendo hongos con paginación...${RESET}"
    read -p "Número de página [1]: " page
    page=${page:-1}
    read -p "Elementos por página [10]: " limit
    limit=${limit:-10}
    
    response=$(curl -s -X GET "$BASE_URL/fungi/page/$page/limit/$limit" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Crear nuevo hongo (requiere autenticación admin)
create_fungus() {
    if ! check_token; then
        return
    fi
    
    JWT_TOKEN=$(cat "$TOKEN_FILE")
    
    echo -e "${YELLOW}Creando nuevo hongo...${RESET}"
    echo -e "${CYAN}Introduce los datos del hongo:${RESET}"
    read -p "Nombre científico: " name
    read -p "Nombre común: " common_name
    read -p "Comestibilidad [Comestible/Tóxico/No comestible]: " edibility
    read -p "Hábitat: " habitat
    read -p "Observaciones: " observations
    read -p "Sinónimo (opcional): " synonym
    
    # Crear JSON con los datos proporcionados
    json_data="{"
    json_data+="\"name\":\"$name\","
    json_data+="\"common_name\":\"$common_name\","
    json_data+="\"edibility\":\"$edibility\","
    json_data+="\"habitat\":\"$habitat\","
    json_data+="\"observations\":\"$observations\""
    
    # Añadir sinónimo solo si se proporcionó
    if [ ! -z "$synonym" ]; then
        json_data+=",\"synonym\":\"$synonym\""
    fi
    
    json_data+="}"
    
    response=$(curl -s -X POST "$BASE_URL/fungi" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}" \
        -d "$json_data")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Actualizar hongo existente (requiere autenticación admin)
update_fungus() {
    if ! check_token; then
        return
    fi
    
    JWT_TOKEN=$(cat "$TOKEN_FILE")
    
    echo -e "${YELLOW}Actualizando hongo existente...${RESET}"
    read -p "ID del hongo a actualizar: " id
    
    # Primero obtener los datos actuales
    current_data=$(curl -s -X GET "$BASE_URL/fungi/$id" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    if ! echo "$current_data" | grep -q "\"success\":true"; then
        echo -e "${RED}No se pudo obtener información del hongo con ID $id${RESET}"
        return
    fi
    
    echo -e "${CYAN}Datos actuales:${RESET}"
    echo "$current_data" | format_json
    
    echo -e "${CYAN}Introduce los nuevos datos (deja en blanco para mantener el valor actual):${RESET}"
    
    # Extraer valores actuales para mostrarlos como default
    current_name=$(echo "$current_data" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
    current_common=$(echo "$current_data" | grep -o '"common_name":"[^"]*"' | cut -d'"' -f4)
    current_edibility=$(echo "$current_data" | grep -o '"edibility":"[^"]*"' | cut -d'"' -f4)
    current_habitat=$(echo "$current_data" | grep -o '"habitat":"[^"]*"' | cut -d'"' -f4)
    current_observations=$(echo "$current_data" | grep -o '"observations":"[^"]*"' | cut -d'"' -f4)
    
    read -p "Nombre científico [$current_name]: " name
    read -p "Nombre común [$current_common]: " common_name
    read -p "Comestibilidad [$current_edibility]: " edibility
    read -p "Hábitat [$current_habitat]: " habitat
    read -p "Observaciones [$current_observations]: " observations
    
    # Usar los valores actuales si no se proporcionan nuevos
    name=${name:-$current_name}
    common_name=${common_name:-$current_common}
    edibility=${edibility:-$current_edibility}
    habitat=${habitat:-$current_habitat}
    observations=${observations:-$current_observations}
    
    # Crear JSON con los datos actualizados
    json_data="{"
    json_data+="\"name\":\"$name\","
    json_data+="\"common_name\":\"$common_name\","
    json_data+="\"edibility\":\"$edibility\","
    json_data+="\"habitat\":\"$habitat\","
    json_data+="\"observations\":\"$observations\""
    json_data+="}"
    
    response=$(curl -s -X PUT "$BASE_URL/fungi/$id" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}" \
        -d "$json_data")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Eliminar hongo por ID (requiere autenticación admin)
delete_fungus() {
    if ! check_token; then
        return
    fi
    
    JWT_TOKEN=$(cat "$TOKEN_FILE")
    
    echo -e "${YELLOW}Eliminando hongo...${RESET}"
    read -p "ID del hongo a eliminar: " id
    
    response=$(curl -s -X DELETE "$BASE_URL/fungi/$id" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Obtener información del perfil (requiere autenticación)
get_profile() {
    if ! check_token; then
        return
    fi
    
    JWT_TOKEN=$(cat "$TOKEN_FILE")
    
    echo -e "${YELLOW}Obteniendo perfil de usuario...${RESET}"
    
    response=$(curl -s -X GET "$BASE_URL/users/profile" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Marcar un hongo como favorito
add_to_favorites() {
    if ! check_token; then
        return
    fi
    
    JWT_TOKEN=$(cat "$TOKEN_FILE")
    
    echo -e "${YELLOW}Añadir hongo a favoritos...${RESET}"
    read -p "ID del hongo: " id
    
    response=$(curl -s -X POST "$BASE_URL/user/favorites/$id" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Dar like a un hongo
like_fungus() {
    if ! check_token; then
        return
    fi
    
    JWT_TOKEN=$(cat "$TOKEN_FILE")
    
    echo -e "${YELLOW}Dar like a un hongo...${RESET}"
    read -p "ID del hongo: " id
    
    response=$(curl -s -X POST "$BASE_URL/user/likes/$id" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Ver mis hongos favoritos
view_favorites() {
    if ! check_token; then
        return
    fi
    
    JWT_TOKEN=$(cat "$TOKEN_FILE")
    
    echo -e "${YELLOW}Obteniendo mis hongos favoritos...${RESET}"
    
    response=$(curl -s -X GET "$BASE_URL/user/favorites" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Accept-Language: ${CURRENT_LANG:-$DEFAULT_LANG}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Ejecutar el menú principal
main() {
    show_banner
    
    # Configurar idioma por defecto
    CURRENT_LANG=$DEFAULT_LANG
    
    while true; do
        show_menu
        read -p "Selecciona una opción: " option
        
        case $option in
            1) register_user ;;
            2) login ;;
            3) check_status ;;
            4) list_fungi ;;
            5) get_fungus_by_id ;;
            6) get_random_fungus ;;
            7) search_fungi_by_field ;;
            8) list_fungi_paginated ;;
            9) create_fungus ;;
            10) update_fungus ;;
            11) delete_fungus ;;
            12) get_profile ;;
            13) add_to_favorites ;;
            14) like_fungus ;;
            15) view_favorites ;;
            16) select_language ;;
            0) 
                echo -e "${GREEN}¡Hasta luego!${RESET}"
                exit 0 
                ;;
            *) 
                echo -e "${RED}Opción no válida${RESET}" 
                ;;
        esac
    done
}

# Ejecutar el programa
main 