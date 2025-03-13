#!/bin/bash

# Colores para los mensajes
GREEN="\033[0;32m"
YELLOW="\033[1;33m"
CYAN="\033[0;36m"
RED="\033[0;31m"
RESET="\033[0m"

# Variables por defecto
HOST="localhost"
PORT="${1:-8080}"
BASE_URL="http://$HOST:$PORT/api"
TOKEN_FILE="/tmp/fungi_api_token.txt"
USER_FILE="/tmp/fungi_api_user.txt"

# Banner del script
show_banner() {
    echo -e "${CYAN}════════════════════════════════════════════════════════════════════${RESET}"
    echo -e "                ${GREEN}API FUNGI TESTER${RESET} - Puerto: ${YELLOW}$PORT${RESET}"
    echo -e "${CYAN}════════════════════════════════════════════════════════════════════${RESET}"
}

# Función para mostrar el menú
show_menu() {
    echo -e "\n${YELLOW}Opciones disponibles:${RESET}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
    echo -e "${YELLOW}1.${RESET} Registrar nuevo usuario"
    echo -e "${YELLOW}2.${RESET} Iniciar sesión (obtener token JWT)"
    echo -e "${YELLOW}3.${RESET} Ver estado actual (usuario y token)"
    echo -e "${YELLOW}4.${RESET} Listar hongos"
    echo -e "${YELLOW}5.${RESET} Obtener hongo por ID"
    echo -e "${YELLOW}6.${RESET} Obtener hongo aleatorio"
    echo -e "${YELLOW}7.${RESET} Crear nuevo hongo (requiere autenticación)"
    echo -e "${YELLOW}8.${RESET} Eliminar hongo por ID (requiere autenticación admin)"
    echo -e "${YELLOW}9.${RESET} Obtener información del perfil (requiere autenticación)"
    echo -e "${YELLOW}0.${RESET} Salir"
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

# Registrar un nuevo usuario
register_user() {
    echo -e "${YELLOW}Registrando nuevo usuario...${RESET}"
    read -p "Nombre de usuario: " username
    read -p "Email: " email
    read -s -p "Contraseña: " password
    echo ""
    
    response=$(curl -s -X POST "$BASE_URL/users" \
        -H "Content-Type: application/json" \
        -d "{\"username\":\"$username\",\"email\":\"$email\",\"password\":\"$password\"}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
    
    # Guarda el nombre de usuario para facilitar el login posterior
    if echo "$response" | grep -q "\"success\":true"; then
        echo "$username" > "$USER_FILE"
        echo -e "${GREEN}Usuario guardado para inicio de sesión rápido${RESET}"
    fi
}

# Iniciar sesión y obtener token JWT
login() {
    echo -e "${YELLOW}Iniciando sesión...${RESET}"
    
    # Si existe un usuario guardado, sugerir usarlo
    if [ -f "$USER_FILE" ]; then
        saved_user=$(cat "$USER_FILE")
        read -p "Nombre de usuario [$saved_user]: " username
        username=${username:-$saved_user}
    else
        read -p "Nombre de usuario: " username
    fi
    
    read -s -p "Contraseña: " password
    echo ""
    
    response=$(curl -s -X POST "$BASE_URL/auth/login" \
        -H "Content-Type: application/json" \
        -d "{\"username\":\"$username\",\"password\":\"$password\"}")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
    
    # Extraer y guardar el token JWT si el login fue exitoso
    if echo "$response" | grep -q "\"success\":true"; then
        token=$(echo "$response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
        echo "$token" > "$TOKEN_FILE"
        echo -e "${GREEN}Token JWT guardado correctamente${RESET}"
        
        # Guardar el nombre de usuario también
        echo "$username" > "$USER_FILE"
    else
        echo -e "${RED}Error al iniciar sesión${RESET}"
    fi
}

# Ver estado actual del usuario y token
check_status() {
    echo -e "${YELLOW}Estado actual:${RESET}"
    
    if [ -f "$USER_FILE" ]; then
        user=$(cat "$USER_FILE")
        echo -e "${GREEN}Usuario guardado:${RESET} $user"
    else
        echo -e "${RED}No hay usuario guardado${RESET}"
    fi
    
    if [ -f "$TOKEN_FILE" ]; then
        token=$(cat "$TOKEN_FILE")
        token_short="${token:0:20}...${token: -20}"
        echo -e "${GREEN}Token JWT:${RESET} $token_short"
        
        # Verificar si el token es válido haciendo una petición simple
        response=$(curl -s -X GET "$BASE_URL/users/profile" \
            -H "Authorization: Bearer $token")
        
        if echo "$response" | grep -q "\"success\":true"; then
            echo -e "${GREEN}Estado del token:${RESET} Válido"
        else
            echo -e "${RED}Estado del token:${RESET} Inválido o expirado"
        fi
    else
        echo -e "${RED}No hay token JWT guardado${RESET}"
    fi
}

# Listar hongos
list_fungi() {
    echo -e "${YELLOW}Listando hongos...${RESET}"
    read -p "Página [1]: " page
    page=${page:-1}
    read -p "Límite por página [10]: " limit
    limit=${limit:-10}
    
    response=$(curl -s -X GET "$BASE_URL/fungi/page/$page/limit/$limit")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Obtener hongo por ID
get_fungus_by_id() {
    echo -e "${YELLOW}Obteniendo hongo por ID...${RESET}"
    read -p "ID del hongo: " id
    
    response=$(curl -s -X GET "$BASE_URL/fungi/$id")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Obtener hongo aleatorio
get_random_fungus() {
    echo -e "${YELLOW}Obteniendo hongo aleatorio...${RESET}"
    
    response=$(curl -s -X GET "$BASE_URL/fungi/random")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Crear nuevo hongo (requiere autenticación)
create_fungus() {
    if ! check_token; then
        return
    fi
    
    JWT_TOKEN=$(cat "$TOKEN_FILE")
    
    echo -e "${YELLOW}Creando nuevo hongo...${RESET}"
    read -p "Nombre científico: " name
    read -p "Nombre común: " common_name
    read -p "Comestibilidad [edible/inedible/poisonous/unknown]: " edibility
    read -p "Hábitat: " habitat
    read -p "Observaciones: " observations
    
    response=$(curl -s -X POST "$BASE_URL/fungi" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -d "{\"name\":\"$name\",\"common_name\":\"$common_name\",\"edibility\":\"$edibility\",\"habitat\":\"$habitat\",\"observations\":\"$observations\"}")
    
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
        -H "Authorization: Bearer $JWT_TOKEN")
    
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
        -H "Authorization: Bearer $JWT_TOKEN")
    
    echo -e "${GREEN}Respuesta:${RESET}"
    echo "$response" | format_json
}

# Ejecutar el menú principal
main() {
    show_banner
    
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
            7) create_fungus ;;
            8) delete_fungus ;;
            9) get_profile ;;
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