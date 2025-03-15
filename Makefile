# Makefile para gestionar las operaciones comunes del proyecto
# mgrl39

# Colores para los mensajes
GREEN := \033[0;32m
YELLOW := \033[1;33m
CYAN := \033[0;36m
RED := \033[0;31m
RESET := \033[0m

# Variables del proyecto
GITHUB_USER := mgrl39
GITHUB_URL := https://github.com/$(GITHUB_USER)
API_TEST_SCRIPT := tools/test_api.sh

# Comando para abrir URLs según el sistema operativo
ifeq ($(shell uname), Darwin)
	OPEN := open
else
	OPEN := xdg-open
endif

# Variables para verificación de rutas
SERVER_HOST := localhost
PORT := 8080
SERVER_URL := http://$(SERVER_HOST):$(PORT)
ROUTES_TO_CHECK := / /index /login /register /about /contact /terms /faq /profile /favorites /statistics /admin /fungus /random /docs/api
TIMEOUT := 3

# Variables para gettext
LOCALES_DIR := locales
LANGUAGES := es_ES ca_ES en_US
LC_MESSAGES := LC_MESSAGES
PO_FILES := $(foreach lang,$(LANGUAGES),$(LOCALES_DIR)/$(lang)/$(LC_MESSAGES)/messages.po)
MO_FILES := $(PO_FILES:.po=.mo)

.PHONY: help init save-db repos install clean test log status check-routes check-routes-port translations test-api

help:
	@echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)"
	@echo "                      COMANDOS DISPONIBLES                          "
	@echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)"
	@echo "$(YELLOW)make init$(RESET)      - Inicializa el entorno de desarrollo (requiere sudo)"
	@echo "$(YELLOW)make save-db$(RESET)   - Guarda la estructura actual de la base de datos"
	@echo "$(YELLOW)make repos$(RESET)     - Abre los repositorios de GitHub del usuario"
	@echo "$(YELLOW)make install$(RESET)   - Instala las dependencias del proyecto"
	@echo "$(YELLOW)make status$(RESET)    - Muestra el estado actual del proyecto"
	@echo "$(YELLOW)make check-routes$(RESET) - Verifica rutas (pregunta por el puerto)"
	@echo "$(YELLOW)make check-routes-port p=XXXX$(RESET) - Verifica rutas en puerto específico"
	@echo "$(YELLOW)make translations$(RESET) - Genera los archivos .mo de las traducciones"
	@echo "$(YELLOW)make test-api$(RESET)  - Ejecuta el script de pruebas de la API"
	@echo "$(YELLOW)make test-api p=XXXX$(RESET) - Ejecuta pruebas de API en puerto específico"
	@echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)"

# Inicializa el entorno
init:
	@echo "$(GREEN)Inicializando el entorno de desarrollo...$(RESET)"
	@if [ "$$(id -u)" != "0" ]; then \
		echo "$(RED)This command must be executed as root (sudo make init)$(RESET)"; \
		exit 1; \
	fi
	@sudo ./tools/init.sh
	@echo "$(GREEN)Inicialización completada.$(RESET)"

# Guarda la estructura de la base de datos
save-db:
	@echo "$(GREEN)Guardando la estructura de la base de datos...$(RESET)"
	@if [ "$$(id -u)" != "0" ]; then \
		echo "$(RED)This command must be executed as root (sudo make save-db)$(RESET)"; \
		exit 1; \
	fi
	@./tools/bd_saver.sh
	@echo "$(GREEN)Base de datos guardada correctamente.$(RESET)"

github:
	@echo "$(GREEN)Abriendo Github de $(GITHUB_USER)...$(RESET)"
	@$(OPEN) $(GITHUB_URL)

# Instala dependencias
install:
	@echo "$(GREEN)Instalando dependencias del proyecto...$(RESET)"
	@echo "Esta funcionalidad aún no está implementada"
	@echo "$(GREEN)Instalación completada.$(RESET)"

# Ejecuta pruebas
test:
	@echo "$(GREEN)Ejecutando pruebas...$(RESET)"
	@echo "Esta funcionalidad aún no está implementada"
	@echo "$(GREEN)Pruebas completadas.$(RESET)"

# Muestra el estado del proyecto
status:
	@echo "$(GREEN)Estado del proyecto:$(RESET)"
	@echo "$(YELLOW)Sistema: $(RESET)$(shell uname -s)"
	@echo "$(YELLOW)Apache: $(RESET)$(shell systemctl is-active apache2 2>/dev/null || echo 'no instalado')"
	@echo "$(YELLOW)MySQL: $(RESET)$(shell systemctl is-active mysql 2>/dev/null || echo 'no instalado')"
	@echo "$(YELLOW)Espacio de disco: $(RESET)$(shell df -h . | grep -v Filesystem | awk '{print $$4 " disponible"}')"

# Genera los archivos .mo de las traducciones
translations:
	@echo "$(GREEN)Generando archivos de traducción .mo...$(RESET)"
	@for lang in $(LANGUAGES); do \
		if [ -f "$(LOCALES_DIR)/$$lang/$(LC_MESSAGES)/messages.po" ]; then \
			echo "$(GREEN)Procesando $$lang...$(RESET)"; \
			msgfmt -o "$(LOCALES_DIR)/$$lang/$(LC_MESSAGES)/messages.mo" "$(LOCALES_DIR)/$$lang/$(LC_MESSAGES)/messages.po"; \
			echo "$(GREEN)Archivo .mo generado correctamente para $$lang$(RESET)"; \
		else \
			echo "$(YELLOW)Advertencia: No se encontró el archivo messages.po para $$lang$(RESET)"; \
		fi; \
	done
	@echo "$(GREEN)Proceso de traducción completado.$(RESET)"

# Verifica las rutas de la página
check-routes:
	@echo "$(YELLOW)¿En qué puerto está ejecutándose la aplicación? [$(PORT)]: $(RESET)" && read input_port && \
	PORT_TO_USE=$${input_port:-$(PORT)} && \
	SERVER_URL_FINAL="http://$(SERVER_HOST):$$PORT_TO_USE" && \
	echo "$(GREEN)Verificando rutas en $$SERVER_URL_FINAL ...$(RESET)" && \
	echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)" && \
	echo "$(YELLOW)RUTA$(RESET)                      $(YELLOW)ESTADO$(RESET)        $(YELLOW)TIEMPO$(RESET)         $(YELLOW)RESULTADO$(RESET)" && \
	echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)" && \
	for route in $(ROUTES_TO_CHECK); do \
		HTTP_CODE=$$(curl -o /dev/null -s -w "%{http_code}" --max-time $(TIMEOUT) $$SERVER_URL_FINAL$$route); \
		TIME=$$(curl -o /dev/null -s -w "%{time_total}" --max-time $(TIMEOUT) $$SERVER_URL_FINAL$$route); \
		if [ $$HTTP_CODE -eq 200 ]; then \
			printf "$(YELLOW)%-25s$(RESET) $(GREEN)%-10s$(RESET) $(CYAN)%-10s$(RESET) $(GREEN)✓ OK$(RESET)\n" $$route $$HTTP_CODE $$TIME; \
		else \
			printf "$(YELLOW)%-25s$(RESET) $(RED)%-10s$(RESET) $(CYAN)%-10s$(RESET) $(RED)✗ ERROR$(RESET)\n" $$route $$HTTP_CODE $$TIME; \
		fi \
	done && \
	echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)" && \
	echo "$(GREEN)Verificación de rutas completada.$(RESET)"

# También puedes especificar el puerto directamente al ejecutar el comando
check-routes-port:
	@if [ -z "$(p)" ]; then \
		echo "$(RED)Debes especificar un puerto. Ejemplo: make check-routes-port p=8000$(RESET)"; \
		exit 1; \
	fi; \
	SERVER_URL_FINAL="http://$(SERVER_HOST):$(p)" && \
	echo "$(GREEN)Verificando rutas en $$SERVER_URL_FINAL ...$(RESET)" && \
	echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)" && \
	echo "$(YELLOW)RUTA$(RESET)                      $(YELLOW)ESTADO$(RESET)        $(YELLOW)TIEMPO$(RESET)         $(YELLOW)RESULTADO$(RESET)" && \
	echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)" && \
	for route in $(ROUTES_TO_CHECK); do \
		HTTP_CODE=$$(curl -o /dev/null -s -w "%{http_code}" --max-time $(TIMEOUT) $$SERVER_URL_FINAL$$route); \
		TIME=$$(curl -o /dev/null -s -w "%{time_total}" --max-time $(TIMEOUT) $$SERVER_URL_FINAL$$route); \
		if [ $$HTTP_CODE -eq 200 ]; then \
			printf "$(YELLOW)%-25s$(RESET) $(GREEN)%-10s$(RESET) $(CYAN)%-10s$(RESET) $(GREEN)✓ OK$(RESET)\n" $$route $$HTTP_CODE $$TIME; \
		else \
			printf "$(YELLOW)%-25s$(RESET) $(RED)%-10s$(RESET) $(CYAN)%-10s$(RESET) $(RED)✗ ERROR$(RESET)\n" $$route $$HTTP_CODE $$TIME; \
		fi \
	done && \
	echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)" && \
	echo "$(GREEN)Verificación de rutas completada.$(RESET)"

# Ejecuta pruebas de la API
test-api:
	@echo "$(GREEN)Ejecutando pruebas de la API...$(RESET)"
	@if [ ! -f "./$(API_TEST_SCRIPT)" ]; then \
		echo "$(RED)Error: Script de prueba de API no encontrado en $(API_TEST_SCRIPT)$(RESET)"; \
		exit 1; \
	fi
	@if [ ! -x "./$(API_TEST_SCRIPT)" ]; then \
		echo "$(YELLOW)Haciendo ejecutable el script de prueba...$(RESET)"; \
		chmod +x ./$(API_TEST_SCRIPT); \
	fi
	@if [ -z "$(p)" ]; then \
		echo "$(YELLOW)¿En qué puerto está ejecutándose la aplicación? [$(PORT)]: $(RESET)" && read input_port && \
		PORT_TO_USE=$${input_port:-$(PORT)}; \
		./$(API_TEST_SCRIPT) $$PORT_TO_USE; \
	else \
		./$(API_TEST_SCRIPT) $(p); \
	fi
