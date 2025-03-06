document.getElementById('cards-view').addEventListener('click', function() {
    // Remover clase active de todos los botones
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Agregar clase active al botón de tarjetas
    this.classList.add('active');
    
    // Cambiar la vista del contenedor
    const container = document.querySelector('.items-container'); // ajusta el selector según tu HTML
    container.classList.remove('grid-view-container', 'list-view-container');
    container.classList.add('cards-view-container');
}); 