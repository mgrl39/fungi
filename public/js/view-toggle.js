document.addEventListener('DOMContentLoaded', function() {
    const gridView = document.getElementById('grid-view');
    const listView = document.getElementById('list-view');
    const fungiContainer = document.getElementById('fungi-container');

    gridView.addEventListener('click', function() {
        fungiContainer.classList.remove('list-view');
        fungiContainer.classList.add('grid-view');
        gridView.classList.add('active');
        listView.classList.remove('active');
        // Guardar preferencia en localStorage
        localStorage.setItem('viewPreference', 'grid');
    });

    listView.addEventListener('click', function() {
        fungiContainer.classList.remove('grid-view');
        fungiContainer.classList.add('list-view');
        listView.classList.add('active');
        gridView.classList.remove('active');
        // Guardar preferencia en localStorage
        localStorage.setItem('viewPreference', 'list');
    });

    // Cargar preferencia guardada
    const savedView = localStorage.getItem('viewPreference');
    if (savedView === 'list') {
        listView.click();
    } else {
        gridView.click();
    }
}); 