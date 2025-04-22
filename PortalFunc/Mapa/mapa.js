// Asegúrate de que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Inicialización del mapa con el centro en Lima, Perú
    const map = L.map('map', {
        center: [-12.0464, -77.0428],
        zoom: 12
    });

    // Capa base de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18,
    }).addTo(map);

    // Grupo de clusters para los marcadores
    const markersCluster = L.markerClusterGroup();

    // Iconos personalizados para diferentes tipos de clientes
    function createCustomIcon(iconClass, color) {
        return L.divIcon({
            html: `<i class="fas ${iconClass}" style="color: ${color}; font-size: 20px;"></i>`,
            iconSize: [30, 30],
            className: 'custom-marker-icon'
        });
    }

    const iconResidential = createCustomIcon('fa-home', '#4CAF50');
    const iconCommercial = createCustomIcon('fa-store', '#2196F3');
    const iconIndustrial = createCustomIcon('fa-industry', '#F44336');

    // Datos de ejemplo de clientes
    const clientes = [
        { 
            id: 1,
            nombre: "Cliente Residencial 1", 
            direccion: "Av. Universitaria 1503, San Miguel, Lima, Perú", 
            lat: -12.0464, 
            lon: -77.0428,
            tipo: "Residencial",
            contacto: "Juan Pérez",
            telefono: "987654321"
        },
        { 
            id: 2,
            nombre: "Cliente Comercial 1", 
            direccion: "Calle Javier Prado Este 600, Lince, Lima, Perú", 
            lat: -12.0631, 
            lon: -77.0375,
            tipo: "Comercial",
            contacto: "María Gómez",
            telefono: "987654322"
        },
        { 
            id: 3,
            nombre: "Cliente Industrial 1", 
            direccion: "Av. Pardo y Aliaga 550, Miraflores, Lima, Perú", 
            lat: -12.0889, 
            lon: -77.0320,
            tipo: "Industrial",
            contacto: "Carlos Ruiz",
            telefono: "987654323"
        }
    ];

    // Función para obtener el icono según el tipo de cliente
    function getIconByType(type) {
        switch(type) {
            case 'Residencial': return iconResidential;
            case 'Comercial': return iconCommercial;
            case 'Industrial': return iconIndustrial;
            default: return iconResidential;
        }
    }

    // Función para crear el contenido del popup
    function createPopupContent(cliente) {
        return `
            <div class="popup-content">
                <h3>${cliente.nombre}</h3>
                <p><i class="fas fa-map-marker-alt"></i> ${cliente.direccion}</p>
                <p><i class="fas fa-tag"></i> Tipo: ${cliente.tipo}</p>
                <p><i class="fas fa-user"></i> Contacto: ${cliente.contacto}</p>
                <p><i class="fas fa-phone"></i> Teléfono: ${cliente.telefono}</p>
                <div class="popup-actions">
                    <button class="popup-btn directions" data-lat="${cliente.lat}" data-lon="${cliente.lon}">
                        <i class="fas fa-route"></i> Cómo llegar
                    </button>
                    <a href="/PortalFunc/Mis_Clientes/registro_cliente/detalle.php?id=${cliente.id}" class="popup-btn details">
                        <i class="fas fa-info-circle"></i> Detalles
                    </a>
                </div>
            </div>
        `;
    }

    // Cargar clientes en el mapa
    function loadClients(clients) {
        markersCluster.clearLayers();
        
        clients.forEach(cliente => {
            const marker = L.marker([cliente.lat, cliente.lon], {
                icon: getIconByType(cliente.tipo)
            }).bindPopup(createPopupContent(cliente));
            
            marker.clienteData = cliente;
            markersCluster.addLayer(marker);
        });
        
        map.addLayer(markersCluster);
        
        // Ajustar la vista para mostrar todos los marcadores
        if (clients.length > 0) {
            map.fitBounds(markersCluster.getBounds());
        }
    }

    // Cargar todos los clientes inicialmente
    loadClients(clientes);

    // Botón "Mi ubicación"
    document.getElementById('locate-me')?.addEventListener('click', function() {
        map.locate({setView: true, maxZoom: 15});
    });

    // Buscar cliente
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            if (searchTerm.length > 2) {
                const filtered = clientes.filter(cliente => 
                    cliente.nombre.toLowerCase().includes(searchTerm) || 
                    cliente.direccion.toLowerCase().includes(searchTerm) ||
                    (cliente.contacto && cliente.contacto.toLowerCase().includes(searchTerm)));
                
                loadClients(filtered);
            } else if (searchTerm.length === 0) {
                loadClients(clientes);
            }
        });
    }

    // Filtros
    function applyFilters() {
        const distrito = document.getElementById('filtroDistrito')?.value || '';
        const tipo = document.getElementById('filtroTipo')?.value || '';
        
        let filtered = clientes;
        
        if (distrito) {
            filtered = filtered.filter(cliente => cliente.direccion.includes(distrito));
        }
        
        if (tipo) {
            filtered = filtered.filter(cliente => cliente.tipo === tipo);
        }
        
        loadClients(filtered);
    }

    document.getElementById('filtroDistrito')?.addEventListener('change', applyFilters);
    document.getElementById('filtroTipo')?.addEventListener('change', applyFilters);

    // Botón reiniciar
    document.getElementById('resetMap')?.addEventListener('click', function() {
        if (searchInput) searchInput.value = '';
        const distritoFilter = document.getElementById('filtroDistrito');
        const tipoFilter = document.getElementById('filtroTipo');
        if (distritoFilter) distritoFilter.value = '';
        if (tipoFilter) tipoFilter.value = '';
        loadClients(clientes);
    });

    // Delegación de eventos para los botones del popup
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('directions')) { // Paréntesis cerrados correctamente
            const lat = parseFloat(e.target.dataset.lat);
            const lon = parseFloat(e.target.dataset.lon);
            window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lon}`, '_blank');
        }
    });


    // Toggle sidebar en móviles
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.querySelector('.toggle-btn') || document.createElement('button');
    
    if (!document.querySelector('.toggle-btn')) {
        toggleBtn.className = 'toggle-btn';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(toggleBtn);
    }
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    
    // Cerrar sidebar al hacer clic fuera de él
    document.addEventListener('click', function(e) {
        if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
            sidebar.classList.remove('active');
        }
    });
});