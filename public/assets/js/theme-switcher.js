document.addEventListener('DOMContentLoaded', () => {
    const themeSwitcher = document.getElementById('theme-switcher');
    
    // Función para cambiar el tema
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Guardar preferencia en cookie para PHP
        document.cookie = `theme=${newTheme};path=/;max-age=31536000`;
    }
    
    // Inicializar tema
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Evento para el botón de cambio de tema
    if (themeSwitcher) {
        themeSwitcher.addEventListener('click', toggleTheme);
    }
}); 