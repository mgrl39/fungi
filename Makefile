# Makefile para gestionar las operaciones comunes del proyecto

# Colores para los mensajes
GREEN := \033[0;32m
YELLOW := \033[1;33m
CYAN := \033[0;36m
RESET := \033[0m

# Variables del proyecto
GITHUB_USER := mgrl39
GITHUB_URL := https://github.com/$(GITHUB_USER)

# Comando para abrir URLs según el sistema operativo
ifeq ($(shell uname), Darwin)
	OPEN := open
else
	OPEN := xdg-open
endif

.PHONY: help init save-db repos install clean test log status

# Comando predeterminado al ejecutar solo 'make'
help:
	@echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)"
	@echo "                      COMANDOS DISPONIBLES                          "
	@echo "$(CYAN)════════════════════════════════════════════════════════════════════$(RESET)"
	@echo "$(YELLOW)make init$(RESET)      - Inicializa el entorno de desarrollo (requiere sudo)"
	@echo "$(YELLOW)make save-db$(RESET)   - Guarda la estructura actual de la base de datos"
	@echo "$(YELLOW)make repos$(RESET)     - Abre los repositorios de GitHub del usuario"
	@echo "$(YELLOW)make install$(RESET)   - Instala las dependencias del proyecto"
	@echo "$(YELLOW)make status$(RESET)    - Muestra el estado actual del proyecto"
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
