document.addEventListener('DOMContentLoaded', function() {
    // Configurar los colores para comestibilidad
    const edibilityColors = {
        'excelente-comestible': '#28a745',
        'excelente-comestible-precaucion': '#5cb85c',
        'buen-comestible': '#8bc34a',
        'comestible': '#a5d6a7',
        'comestible-precaucion': '#ffeb3b',
        'sin-valor': '#adb5bd',
        'no-comestible': '#6c757d',
        'toxica': '#ffc107',
        'mortal': '#dc3545'
    };
    
    // Traducción de los valores de comestibilidad para mostrar
    const edibilityLabels = {
        'excelente-comestible': 'Excelente comestible',
        'excelente-comestible-precaucion': 'Excelente (con precaución)',
        'buen-comestible': 'Buen comestible',
        'comestible': 'Comestible',
        'comestible-precaucion': 'Comestible (con precaución)',
        'sin-valor': 'Sin valor culinario',
        'no-comestible': 'No comestible',
        'toxica': 'Tóxica',
        'mortal': 'Mortal'
    };
    
    // Datos del backend - Añadimos verificación para prevenir errores
    const edibilityData = statsData.edibility || [];
    const familyData = statsData.families || [];
    const likesData = statsData.popular || [];
    const favoritesData = statsData.favorites || [];
    
    console.log("Datos de familias:", familyData); // Debugging
    
    // Gráfico de comestibilidad
    const edibilityCtx = document.getElementById('edibilityChart').getContext('2d');
    new Chart(edibilityCtx, {
        type: 'pie',
        data: {
            labels: edibilityData.map(item => edibilityLabels[item.edibility] || item.edibility),
            datasets: [{
                data: edibilityData.map(item => item.count),
                backgroundColor: edibilityData.map(item => edibilityColors[item.edibility] || '#6c757d'),
                borderWidth: 2,
                borderColor: '#343a40'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#fff'
                    }
                }
            }
        }
    });
    
    // Gráfico de familias - Versión mejorada con verificación de datos
    if (familyData && familyData.length > 0) {
        const familyCtx = document.getElementById('familyChart').getContext('2d');
        
        // Colores vibrantes para las familias
        const familyColors = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
            '#fd7e14', '#6f42c1', '#20c997', '#17a2b8', '#6610f2',
            '#6f42c1', '#20c997', '#17a2b8', '#6610f2', '#3498db'
        ];
        
        new Chart(familyCtx, {
            type: 'doughnut',
            data: {
                labels: familyData.map(item => item.name || 'Desconocida'),
                datasets: [{
                    data: familyData.map(item => item.count),
                    backgroundColor: familyColors.slice(0, familyData.length),
                    borderWidth: 2,
                    borderColor: '#343a40'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#fff',
                            font: {
                                size: 11
                            },
                            boxWidth: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    } else {
        console.error("No hay datos de familias disponibles");
        document.getElementById('familyChart').innerHTML = '<div class="text-center p-3">No hay datos disponibles</div>';
    }
    
    // Gráfico de likes
    const likesCtx = document.getElementById('likesChart').getContext('2d');
    new Chart(likesCtx, {
        type: 'bar',
        data: {
            labels: likesData.map(item => item.name),
            datasets: [{
                label: 'Likes',
                data: likesData.map(item => item.likes_count),
                backgroundColor: '#4e73df',
                borderColor: '#4e73df',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#fff'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#fff'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#fff'
                    }
                }
            }
        }
    });
    
    // Gráfico de favoritos
    const favoritesCtx = document.getElementById('favoritesChart').getContext('2d');
    new Chart(favoritesCtx, {
        type: 'bar',
        data: {
            labels: favoritesData.map(item => item.name),
            datasets: [{
                label: 'Favoritos',
                data: favoritesData.map(item => item.favorites_count),
                backgroundColor: '#f6c23e',
                borderColor: '#f6c23e',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#fff'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: '#fff'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#fff'
                    }
                }
            }
        }
    });
});
