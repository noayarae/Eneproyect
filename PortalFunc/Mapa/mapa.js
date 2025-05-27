document.addEventListener('DOMContentLoaded', function() {
    // Configuración inicial mejorada
    const config = {
        defaultCenter: [-12.0464, -77.0428], // Lima centro
        defaultZoom: 12,
        maxClusterRadius: 80,
        districtZoomLevel: 14,
        geocodeBatchSize: 10,
        localStorageKey: 'mapaClientesUltimaUbicacion'
    };

    // Obtener última ubicación guardada o usar la predeterminada
    const savedLocation = localStorage.getItem(config.localStorageKey);
    const initialView = savedLocation ? JSON.parse(savedLocation) : {
        center: config.defaultCenter,
        zoom: config.defaultZoom
    };

    // Inicialización del mapa con la ubicación guardada o predeterminada
    const map = L.map('map', {
        center: initialView.center,
        zoom: initialView.zoom,
        zoomControl: false
    });

    // Guardar la ubicación cuando cambie la vista del mapa
    map.on('moveend', function() {
        const currentCenter = map.getCenter();
        const currentZoom = map.getZoom();
        
        localStorage.setItem(config.localStorageKey, JSON.stringify({
            center: [currentCenter.lat, currentCenter.lng],
            zoom: currentZoom
        }));
    });

    // Capa base de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18,
    }).addTo(map);

    // Control de ubicación mejorado
    L.control.locate({
        position: 'topright',
        drawCircle: true,
        follow: true,
        setView: 'untilPan',
        keepCurrentZoomLevel: true,
        locateOptions: {
            enableHighAccuracy: true,
            maximumAge: 10000,
            timeout: 10000
        },
        icon: 'fas fa-location-arrow',
        metric: true,
        onLocationError: function(err) {
            console.error("Error de ubicación:", err);
            showNotification("No se pudo obtener tu ubicación. Asegúrate de haber permitido el acceso a tu ubicación.", 'error');
        },
        onLocationFound: function(e) {
            showNotification("Ubicación encontrada", 'success');
        }
    }).addTo(map);

    // Grupo de clusters para los marcadores
    const markersCluster = L.markerClusterGroup({
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        maxClusterRadius: config.maxClusterRadius,
        chunkedLoading: true
    });

    // Iconos personalizados
    const markerIcons = {
        daily: createCustomIcon('fa-calendar-day', '#4CAF50'),
        weekly: createCustomIcon('fa-calendar-week', '#2196F3'),
        monthly: createCustomIcon('fa-calendar-alt', '#9C27B0'),
        yearly: createCustomIcon('fa-calendar', '#FF9800'),
        inactive: createCustomIcon('fa-calendar-times', '#F44336'),
        ungeocoded: createCustomIcon('fa-question-circle', '#607D8B')
    };

    // Variables de estado
    const state = {
        allClients: clientesProcesados,
        currentClients: [],
        geocodingQueue: [],
        isGeocoding: false,
        drawnItems: new L.FeatureGroup(),
        ubicacionesCache: {}
    };

    // Inicializar capas
    map.addLayer(state.drawnItems);

    // Función para crear iconos personalizados
    function createCustomIcon(iconClass, color) {
        return L.divIcon({
            html: `<div class="custom-marker" style="background-color: ${color}">
                    <i class="fas ${iconClass}"></i>
                </div>`,
            iconSize: [30, 30],
            className: 'custom-marker-icon'
        });
    }

    // Obtener icono según tipo y estado
    function getIconByTypeAndStatus(cliente) {
        if (!cliente.geocodificado) return markerIcons.ungeocoded;
        if (cliente.estado === 'No vigente') return markerIcons.inactive;
        
        switch(cliente.tipo_credito) {
            case 'Diario': return markerIcons.daily;
            case 'Semanal': return markerIcons.weekly;
            case 'Mensual': return markerIcons.monthly;
            case 'Anual': return markerIcons.yearly;
            default: return markerIcons.daily;
        }
    }

    // Función mejorada para geocodificar clientes
    async function geocodeCliente(cliente) {
        // Primero intentar con distrito exacto si está disponible
        if (cliente.distrito) {
            try {
                const response = await fetch(`ubicaciones.php?action=coordenadas&distrito=${encodeURIComponent(cliente.distrito)}`);
                const data = await response.json();
                
                if (data && data.lat && data.lon) {
                    return {
                        ...cliente,
                        lat: data.lat,
                        lon: data.lon,
                        geocodificado: true,
                        precision: 'distrito'
                    };
                }
            } catch (error) {
                console.error("Error al buscar coordenadas por distrito:", error);
            }
        }

        // Si no se encontró por distrito, usar la API de geocodificación
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(cliente.direccion_completa)}&countrycodes=pe&limit=1&addressdetails=1`);
            
            if (!response.ok) throw new Error("Error en la respuesta");
            
            const data = await response.json();
            
            if (data && data.length > 0) {
                const result = data[0];
                return {
                    ...cliente,
                    lat: parseFloat(result.lat),
                    lon: parseFloat(result.lon),
                    geocodificado: true,
                    departamento: result.address.state || '',
                    provincia: result.address.county || '',
                    distrito: result.address.city_district || result.address.city || result.address.town || '',
                    direccion_formateada: result.display_name,
                    precision: 'direccion'
                };
            }
            
            return cliente;
        } catch (error) {
            console.error("Error en geocodificación:", error);
            return cliente;
        }
    }

    // Crear contenido popup mejorado
    function createPopupContent(cliente) {
        const direccionMostrar = cliente.direccion_formateada || cliente.direccion_completa;
        const precisionText = cliente.geocodificado ? 
            (cliente.precision === 'distrito' ? '(Precisión: Distrito)' : '(Precisión: Dirección aproximada)') : 
            '(Sin geocodificar)';
        
        return `
            <div class="popup-content">
                <h3>${cliente.nombre} ${cliente.apellidos}</h3>
                <p><i class="fas fa-map-marker-alt"></i> ${direccionMostrar} <small>${precisionText}</small></p>
                ${cliente.geocodificado ? `
                    <p><i class="fas fa-tag"></i> Tipo: ${cliente.tipo_credito}</p>
                    <p><i class="fas fa-globe-americas"></i> Departamento: ${cliente.departamento || 'N/A'}</p>
                    <p><i class="fas fa-map-pin"></i> Distrito: ${cliente.distrito || 'N/A'}</p>
                    <p><i class="fas fa-info-circle"></i> Estado: ${cliente.estado}</p>
                    <div class="popup-actions">
                        <button class="popup-btn directions" data-address="${encodeURIComponent(direccionMostrar)}">
                            <i class="fas fa-route"></i> Cómo llegar
                        </button>
                        <button class="popup-btn center" data-lat="${cliente.lat}" data-lon="${cliente.lon}">
                            <i class="fas fa-crosshairs"></i> Centrar
                        </button>
                        <a href="/PortalFunc/Mis_Clientes/registro_cliente/detalle.php?id=${cliente.id}" class="popup-btn details">
                            <i class="fas fa-info-circle"></i> Detalles
                        </a>
                    </div>
                ` : `
                    <div class="popup-warning">
                        <i class="fas fa-exclamation-triangle"></i> Dirección no geocodificada
                    </div>
                    <div class="popup-actions">
                        <button class="popup-btn geocode-single" data-id="${cliente.id}">
                            <i class="fas fa-map-marked-alt"></i> Geocodificar
                        </button>
                    </div>
                `}
            </div>
        `;
    }

    // Cargar clientes en el mapa mejorado
    function loadClients(clients) {
        markersCluster.clearLayers();
        state.currentClients = clients;

        const markers = clients.map(cliente => {
            // Si no está geocodificado, usar posición por defecto con icono diferente
            const position = cliente.geocodificado ? 
                [cliente.lat, cliente.lon] : 
                [config.defaultCenter[0] + (Math.random() * 0.5 - 0.25), 
                 config.defaultCenter[1] + (Math.random() * 0.5 - 0.25)];
            
            const marker = L.marker(position, {
                icon: getIconByTypeAndStatus(cliente),
                title: `${cliente.nombre} ${cliente.apellidos}`
            }).bindPopup(createPopupContent(cliente));
            
            marker.clienteData = cliente;
            return marker;
        }).filter(Boolean);

        markersCluster.addLayers(markers);
        map.addLayer(markersCluster);
        
        updateStats();
    }

    // Actualizar estadísticas
    function updateStats() {
        const total = state.allClients.length;
        const vigentes = state.allClients.filter(c => c.estado === 'Vigente').length;
        const geocodificados = state.allClients.filter(c => c.geocodificado).length;
        const enVista = state.currentClients.length;

        document.getElementById('total-clientes').textContent = total;
        document.getElementById('clientes-vigentes').textContent = vigentes;
        document.getElementById('clientes-geocodificados').textContent = geocodificados;
        document.getElementById('clientes-vista').textContent = enVista;
    }

    // Centrar mapa en coordenadas
    function centerMap(lat, lon, zoom = 15) {
        map.flyTo([lat, lon], zoom, {
            duration: 1,
            easeLinearity: 0.25
        });
    }

    // Aplicar filtros mejorado
    function applyFilters() {
        const filters = getCurrentFilters();
        let filtered = filterClients(state.allClients, filters);
        
        // Aplicar filtro de área dibujada si existe
        filtered = filterClientsInArea(filtered);
        
        loadClients(filtered);
        
        // Si hay filtros de ubicación, centrar en el área
        if (filters.departamento || filters.provincia || filters.distrito) {
            centerOnFilteredArea(filtered);
        }
    }

    // Centrar en el área filtrada
    function centerOnFilteredArea(filteredClients) {
        const geocodificados = filteredClients.filter(c => c.geocodificado);
        
        if (geocodificados.length > 0) {
            const bounds = L.latLngBounds(geocodificados.map(c => [c.lat, c.lon]));
            map.flyToBounds(bounds, { padding: [50, 50] });
        }
    }

    function getCurrentFilters() {
        return {
            searchTerm: document.getElementById('search').value.toLowerCase(),
            departamento: document.getElementById('filtroDepartamento').value,
            provincia: document.getElementById('filtroProvincia').value,
            distrito: document.getElementById('filtroDistrito').value,
            tipo: document.getElementById('filtroTipo').value,
            estado: document.getElementById('filtroEstado').value
        };
    }

    function filterClients(clients, filters) {
        return clients.filter(cliente => {
            // Filtro de búsqueda
            if (filters.searchTerm && 
                !(cliente.nombre + ' ' + cliente.apellidos).toLowerCase().includes(filters.searchTerm) &&
                !cliente.direccion_completa.toLowerCase().includes(filters.searchTerm) &&
                !(cliente.distrito || '').toLowerCase().includes(filters.searchTerm)) {
                return false;
            }
            
            // Filtros de ubicación
            if (filters.departamento && (cliente.departamento || '') !== filters.departamento) return false;
            if (filters.provincia && (cliente.provincia || '') !== filters.provincia) return false;
            if (filters.distrito && (cliente.distrito || '') !== filters.distrito) return false;
            
            // Filtros de atributos
            if (filters.tipo && cliente.tipo_credito !== filters.tipo) return false;
            if (filters.estado && cliente.estado !== filters.estado) return false;
            
            return true;
        });
    }

    function filterClientsInArea(clients) {
        if (state.drawnItems.getLayers().length === 0) return clients;
        
        return clients.filter(cliente => {
            if (!cliente.geocodificado) return false;
            
            const latLng = L.latLng(cliente.lat, cliente.lon);
            return state.drawnItems.getLayers().some(layer => {
                if (layer instanceof L.Polygon || layer instanceof L.Rectangle) {
                    return layer.getBounds().contains(latLng);
                }
                return false;
            });
        });
    }

    // Cargar provincias basado en departamento
    async function loadProvincias(departamento) {
        const $provinciaSelect = $('#filtroProvincia');
        $provinciaSelect.empty().append('<option value=""></option>');
        $provinciaSelect.val(null).trigger('change');
        $provinciaSelect.prop('disabled', !departamento);

        if (!departamento) {
            $('#filtroDistrito').prop('disabled', true);
            return;
        }

        try {
            // Intenta primero con la caché
            if (state.ubicacionesCache[departamento]) {
                const provincias = state.ubicacionesCache[departamento];
                provincias.forEach(provincia => {
                    $provinciaSelect.append(`<option value="${provincia}">${provincia}</option>`);
                });
                return;
            }

            const response = await fetch(`ubicaciones.php?action=provincias&departamento=${encodeURIComponent(departamento)}`);
            const provincias = await response.json();
            
            // Guardar en caché
            state.ubicacionesCache[departamento] = provincias;
            
            provincias.forEach(provincia => {
                $provinciaSelect.append(`<option value="${provincia}">${provincia}</option>`);
            });
        } catch (error) {
            console.error("Error al cargar provincias:", error);
            showNotification("Error al cargar provincias", 'error');
        }
    }

    async function loadDistritos(provincia) {
        const $distritoSelect = $('#filtroDistrito');
        $distritoSelect.empty().append('<option value=""></option>');
        $distritoSelect.val(null).trigger('change');
        $distritoSelect.prop('disabled', !provincia);

        if (!provincia) return;

        try {
            // Intenta primero con la caché
            const cacheKey = `${$('#filtroDepartamento').val()}_${provincia}`;
            if (state.ubicacionesCache[cacheKey]) {
                const distritos = state.ubicacionesCache[cacheKey];
                distritos.forEach(distrito => {
                    $distritoSelect.append(`<option value="${distrito}">${distrito}</option>`);
                });
                return;
            }

            const response = await fetch(`ubicaciones.php?action=distritos&provincia=${encodeURIComponent(provincia)}`);
            const distritos = await response.json();
            
            // Guardar en caché
            state.ubicacionesCache[cacheKey] = distritos;
            
            distritos.forEach(distrito => {
                $distritoSelect.append(`<option value="${distrito}">${distrito}</option>`);
            });
        } catch (error) {
            console.error("Error al cargar distritos:", error);
            showNotification("Error al cargar distritos", 'error');
        }
    }

    // Inicializar Select2 para filtros
    function initSelect2() {
        $('.select2').select2({
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder');
            },
            language: {
                noResults: function() {
                    return "No se encontraron resultados";
                }
            }
        });

        // Actualizar provincias cuando cambia el departamento
        $('#filtroDepartamento').on('change', function() {
            loadProvincias($(this).val());
            applyFilters();
        });

        // Actualizar distritos cuando cambia la provincia
        $('#filtroProvincia').on('change', function() {
            loadDistritos($(this).val());
            applyFilters();
        });

        // Aplicar filtros cuando cambian otros selectores
        $('#filtroDistrito, #filtroTipo, #filtroEstado').on('change', applyFilters);
    }

    // Configurar búsqueda con autocompletado
    function setupSearchAutocomplete() {
        const $searchInput = $('#search');
        const $searchResults = $('#search-results');
        
        $searchInput.on('input', debounce(function() {
            const term = $(this).val().toLowerCase();
            $searchResults.empty().hide();
            
            if (term.length < 2) return;
            
            const matches = state.allClients.filter(cliente =>
                (cliente.nombre + ' ' + cliente.apellidos).toLowerCase().includes(term) ||
                cliente.direccion_completa.toLowerCase().includes(term) ||
                (cliente.distrito || '').toLowerCase().includes(term));
            
            if (matches.length > 0) {
                matches.slice(0, 5).forEach(cliente => {
                    $searchResults.append(
                        `<div class="search-item" data-id="${cliente.id}">
                            <strong>${cliente.nombre} ${cliente.apellidos}</strong><br>
                            <small>${cliente.direccion_completa}</small>
                        </div>`
                    );
                });
                $searchResults.show();
            } else {
                $searchResults.append('<div class="search-item no-results">No se encontraron clientes</div>');
                $searchResults.show();
            }
        }, 300));
        
        // Manejar clic en resultados de búsqueda
        $searchResults.on('click', '.search-item', function() {
            const clienteId = $(this).data('id');
            const cliente = state.allClients.find(c => c.id === clienteId);
            
            if (cliente) {
                $searchInput.val(cliente.nombre + ' ' + cliente.apellidos);
                $searchResults.hide();
                
                if (cliente.geocodificado) {
                    centerMap(cliente.lat, cliente.lon, 16);
                } else {
                    map.flyTo(config.defaultCenter, config.defaultZoom);
                }
            }
        });
        
        // Ocultar resultados al hacer clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-container').length) {
                $searchResults.hide();
            }
        });
    }

    // Función debounce para optimizar búsqueda
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }

    // Manejar acciones de popup
    function handlePopupActions(e) {
        // Cómo llegar (usando dirección)
        if (e.target.classList.contains('directions') || e.target.closest('.directions')) {
            const btn = e.target.classList.contains('directions') ? e.target : e.target.closest('.directions');
            const address = btn.dataset.address;
            window.open(`https://www.google.com/maps/dir/?api=1&destination=${address}`, '_blank');
        }

        // Centrar mapa
        if (e.target.classList.contains('center') || e.target.closest('.center')) {
            const btn = e.target.classList.contains('center') ? e.target : e.target.closest('.center');
            const lat = parseFloat(btn.dataset.lat);
            const lon = parseFloat(btn.dataset.lon);
            centerMap(lat, lon);
        }

        // Geocodificar cliente individual
        if (e.target.classList.contains('geocode-single') || e.target.closest('.geocode-single')) {
            const btn = e.target.classList.contains('geocode-single') ? e.target : e.target.closest('.geocode-single');
            const clienteId = btn.dataset.id;
            geocodeClienteIndividual(clienteId);
        }
    }

    // Geocodificar un cliente individual
    async function geocodeClienteIndividual(clienteId) {
        const cliente = state.allClients.find(c => c.id === clienteId);
        if (!cliente) return;

        $('#geocode-all').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        try {
            const clienteActualizado = await geocodeCliente(cliente);
            
            // Actualizar el cliente en el estado
            const index = state.allClients.findIndex(c => c.id === clienteId);
            if (index !== -1) {
                state.allClients[index] = clienteActualizado;
            }
            
            // Recargar los clientes en el mapa
            applyFilters();
            
            // Mostrar notificación
            showNotification('Cliente geocodificado correctamente', 'success');
        } catch (error) {
            console.error("Error al geocodificar cliente:", error);
            showNotification('Error al geocodificar el cliente', 'error');
        } finally {
            $('#geocode-all').prop('disabled', false).html('<i class="fas fa-map-marked-alt"></i> Geocodificar');
        }
    }

    // Geocodificar todos los clientes
    async function geocodeAllClients() {
        if (state.isGeocoding) return;
        state.isGeocoding = true;
        
        $('#geocode-all').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Preparando...');

        try {
            // Filtrar solo clientes no geocodificados
            const clientesParaGeocodificar = state.allClients.filter(c => !c.geocodificado);
            
            if (clientesParaGeocodificar.length === 0) {
                showNotification('Todos los clientes ya están geocodificados', 'info');
                return;
            }

            // Geocodificar en lotes para no saturar el servidor
            for (let i = 0; i < clientesParaGeocodificar.length; i += config.geocodeBatchSize) {
                const lote = clientesParaGeocodificar.slice(i, i + config.geocodeBatchSize);
                const clientesGeocodificados = await Promise.all(lote.map(geocodeCliente));
                
                // Actualizar el estado
                clientesGeocodificados.forEach(clienteActualizado => {
                    const index = state.allClients.findIndex(c => c.id === clienteActualizado.id);
                    if (index !== -1) {
                        state.allClients[index] = clienteActualizado;
                    }
                });
                
                // Actualizar el mapa y estadísticas
                applyFilters();
                
                // Pequeña pausa entre lotes
                if (i + config.geocodeBatchSize < clientesParaGeocodificar.length) {
                    await new Promise(resolve => setTimeout(resolve, 2000));
                }
            }
            
            showNotification('Geocodificación completada', 'success');
        } catch (error) {
            console.error("Error en geocodificación masiva:", error);
            showNotification('Error en la geocodificación', 'error');
        } finally {
            state.isGeocoding = false;
            $('#geocode-all').prop('disabled', false).html('<i class="fas fa-map-marked-alt"></i> Geocodificar');
        }
    }

    // Mostrar notificación
    function showNotification(message, type) {
        // Eliminar notificaciones anteriores
        document.querySelectorAll('.notification').forEach(el => el.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }

    // Reiniciar mapa
    function resetMap() {
        state.drawnItems.clearLayers();
        $('#search').val('');
        $('.select2').val(null).trigger('change');
        loadClients(state.allClients);
        map.flyTo(config.defaultCenter, config.defaultZoom);
    }

    // Inicializar la aplicación
    function init() {
        loadClients(state.allClients);
        initSelect2();
        setupSearchAutocomplete();
        
        // Configurar eventos
        document.addEventListener('click', handlePopupActions);
        $('#resetMap').on('click', resetMap);
        $('#locate-me').on('click', () => map.locate({setView: true, maxZoom: 15}));
        $('#add-client').on('click', () => window.location.href = '/Eneproyect/PortalFunc/Mis_Clientes/registro_cliente/index.php');
        $('#geocode-all').on('click', geocodeAllClients);
    }

    // Iniciar
    init();
});