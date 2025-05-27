document.addEventListener('DOMContentLoaded', function() {
    // ==================== ELEMENTOS DEL DOM ====================
    const sidebar = document.querySelector('.sidebar');
    const toggleSidebar = document.querySelector('.toggle-sidebar');
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const calendarEl = document.getElementById('calendar');
    const calendarViewBtn = document.getElementById('calendar-view-btn');
    const agendaViewBtn = document.getElementById('agenda-view-btn');
    const newEventBtn = document.getElementById('new-event-btn');
    const calendarContainer = document.getElementById('calendar-container');
    const agendaView = document.getElementById('agenda-view');
    const agendaList = document.getElementById('agenda-list');
    const agendaFilter = document.getElementById('agenda-filter');
    const eventModal = document.getElementById('event-modal');
    const eventForm = document.getElementById('event-form');
    const cancelEventBtn = document.getElementById('cancel-event-btn');
    const deleteEventBtn = document.getElementById('delete-event-btn');
    const modalTitle = document.getElementById('modal-title');
    const eventClientSelect = document.getElementById('event-client');
    const eventTypeSelect = document.getElementById('event-type');

    // ==================== VARIABLES GLOBALES ====================
    let calendar;

    // ==================== FUNCIONES AUXILIARES ====================
    function formatTime(date) {
        return date.toLocaleTimeString('es-ES', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false 
        });
    }

    function formatDate(date) {
        return date.toLocaleDateString('es-ES', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    function getEventTypeName(type) {
        const typeMap = {
            'consulta': 'Consulta',
            'reunion': 'Reunión',
            'volver_informar': 'Visita a informar',
            'Inicio caso prejudicial': 'Caso Prejudicial',
            'Notificacion': 'Notificación',
            'Amortizacion': 'Amortización',
            'Cambio Gestor': 'Cambio Gestor',
            'Postergacion': 'Postergación',
            'Fin de caso': 'Fin de caso',
            'Pasa a Judicial': 'Judicial'
        };
        return typeMap[type] || type;
    }

    function initializeEventTypeSelect() {
        eventTypeSelect.innerHTML = `
            <option value="" disabled selected>Seleccionar tipo...</option>
            <option value="consulta">Consulta</option>
            <option value="reunion">Reunión</option>
            <option value="volver_informar">Visita a informar</option>                                
            <option value="Inicio caso prejudicial">Inicio caso prejudicial</option>
            <option value="Notificacion">Notificación</option>
            <option value="Amortizacion">Amortización</option>
            <option value="Cambio Gestor">Cambio Gestor</option>
            <option value="Postergacion">Postergación</option>
            <option value="Fin de caso">Fin de caso</option>
            <option value="Pasa a Judicial">Pasa a Judicial</option>
        `;
    }

    // ==================== FUNCIONES PRINCIPALES ====================
    async function fetchClients() {
        try {
            eventClientSelect.innerHTML = '<option value="" disabled selected>Cargando clientes...</option>';
            
            const response = await fetch('obtener_clientes.php');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Error al cargar clientes');
            }
            
            eventClientSelect.innerHTML = '<option value="" disabled selected>Seleccionar cliente...</option>';
            
            result.data.forEach(client => {
                const option = document.createElement('option');
                option.value = client.dni;
                option.textContent = client.name;
                eventClientSelect.appendChild(option);
            });
            
            return result.data;
        } catch (error) {
            console.error('Error cargando clientes:', error);
            eventClientSelect.innerHTML = '<option value="" disabled selected>Error al cargar clientes</option>';
            Swal.fire('Error', 'No se pudieron cargar los clientes', 'error');
            return [];
        }
    }

    async function loadEvents() {
        try {
            const response = await fetch('obtener_eventos.php');
            const result = await response.json();
            
            console.log("Datos recibidos del servidor:", result);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            if (!result.success) {
                throw new Error(result.error || 'Error al cargar eventos');
            }
            
            // Limpiar calendario antes de agregar nuevos eventos
            calendar.removeAllEvents();
            
            // Agregar eventos al calendario
            if (result.data && result.data.length > 0) {
                calendar.addEventSource(result.data);
                
                // Devolver los eventos para usarlos en la agenda
                return result.data;
            }
            
            return [];
        } catch (error) {
            console.error('Error al cargar eventos:', error);
            showAlert('error', 'Error al cargar eventos', error.message);
            return [];
        }
    }

    async function saveEvent(eventData) {
        try {
            console.log('Enviando datos del evento:', eventData);
            
            const response = await fetch('guardar_evento.php', {  
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(eventData)
            });
            
            const responseText = await response.text();
            console.log('Respuesta completa del servidor:', responseText);
            
            let jsonResponse;
            try {
                jsonResponse = JSON.parse(responseText);
            } catch (e) {
                console.error('Error al parsear respuesta JSON:', e);
                throw new Error('El servidor devolvió una respuesta no JSON: ' + responseText);
            }
            
            if (!response.ok) {
                throw new Error(jsonResponse.error || 'Error al guardar el evento');
            }
            
            return jsonResponse;
        } catch (error) {
            console.error('Error al guardar evento:', error);
            throw error;
        }
    }

    async function deleteEvent(eventId) {
        try {
            const response = await fetch('eliminar_evento.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ eventId })
            });
            
            if (!response.ok) throw new Error('Error al eliminar');
            
            return await response.json();
        } catch (error) {
            console.error('Error:', error);
            throw error;
        }
    }

    // ==================== MANEJO DE INTERFAZ ====================
    async function openEventModal(event = null) {
        if (eventClientSelect.options.length <= 1) {
            await fetchClients();
        }
    
        initializeEventTypeSelect();
    
        if (event) {
            modalTitle.innerHTML = '<i class="fas fa-edit"></i> Editar Cita';
            document.getElementById('event-id').value = event.id;
            
            // Asegúrate que el cliente está seleccionado
            if (event.extendedProps.clientId) {
                eventClientSelect.value = event.extendedProps.clientId;
            } else {
                console.error('No hay clientId en el evento:', event);
            }
            
            const startDate = new Date(event.start);
            const timezoneOffset = startDate.getTimezoneOffset() * 60000;
            const localDate = new Date(startDate.getTime() - timezoneOffset);
            
            document.getElementById('event-start-date').value = localDate.toISOString().split('T')[0];
            document.getElementById('event-start-time').value = formatTime(startDate);
            
            // Asegúrate que el tipo está seleccionado
            if (event.extendedProps.type) {
                eventTypeSelect.value = event.extendedProps.type;
            } else {
                console.error('No hay type en el evento:', event);
            }
    
            const duration = event.end ? 
                (new Date(event.end) - startDate) / (1000 * 60) : 30;
            document.getElementById('event-duration').value = duration;
    
            document.getElementById('event-notes').value = event.extendedProps.notes || '';
            deleteEventBtn.style.display = 'inline-block';
        } else {
            modalTitle.innerHTML = '<i class="fas fa-calendar-plus"></i> Nueva Cita';
            document.getElementById('event-id').value = '';
            eventForm.reset();
            deleteEventBtn.style.display = 'none';
        }
    
        eventModal.style.display = 'flex';
    }
    
    function createNewEvent(date) {
        const formattedDate = date.toISOString().split('T')[0];
        document.getElementById('event-start-date').value = formattedDate;
        
        const now = new Date();
        let hours = now.getHours();
        let minutes = Math.ceil(now.getMinutes() / 15) * 15;
        
        if (minutes === 60) {
            hours++;
            minutes = 0;
            if (hours === 24) hours = 0;
        }
        
        document.getElementById('event-start-time').value = 
            `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;

        openEventModal();
    }

    function renderAgendaView(events = []) {
        agendaList.innerHTML = '';
        
        if (events.length === 0) {
            agendaList.innerHTML = '<div class="no-events">No hay eventos programados</div>';
            return;
        }
        
        const filterValue = agendaFilter.value;
        const now = new Date();
        
        // Filtrar eventos según la selección
        const filteredEvents = events.filter(event => {
            const eventDate = new Date(event.start);
            
            switch(filterValue) {
                case 'today':
                    return eventDate.toDateString() === now.toDateString();
                case 'week':
                    const startOfWeek = new Date(now);
                    startOfWeek.setDate(now.getDate() - now.getDay());
                    const endOfWeek = new Date(startOfWeek);
                    endOfWeek.setDate(startOfWeek.getDate() + 6);
                    return eventDate >= startOfWeek && eventDate <= endOfWeek;
                case 'month':
                    return eventDate.getMonth() === now.getMonth() && 
                           eventDate.getFullYear() === now.getFullYear();
                default:
                    return true;
            }
        }).sort((a, b) => new Date(a.start) - new Date(b.start)); // Ordenar por fecha
    
        // Agrupar por fecha
        const eventsByDate = {};
        filteredEvents.forEach(event => {
            const dateKey = new Date(event.start).toLocaleDateString('es-ES', {
                weekday: 'long',
                day: 'numeric',
                month: 'long'
            });
            
            if (!eventsByDate[dateKey]) {
                eventsByDate[dateKey] = [];
            }
            eventsByDate[dateKey].push(event);
        });
    
        // Renderizar
        Object.entries(eventsByDate).forEach(([date, dateEvents]) => {
            const dateHeader = document.createElement('div');
            dateHeader.className = 'agenda-date-header';
            dateHeader.textContent = date;
            agendaList.appendChild(dateHeader);
    
            dateEvents.forEach(event => {
                const eventElement = document.createElement('div');
                eventElement.className = 'agenda-item';
                
                const eventDate = new Date(event.start);
                const eventType = event.extendedProps?.type || 'Evento';
                
                eventElement.innerHTML = `
                    <div class="agenda-item-time">${formatTime(eventDate)}</div>
                    <div class="agenda-item-content">
                        <div class="agenda-item-title">
                            <span class="event-type-badge ${eventType.replace(/\s+/g, '-')}">
                                ${getEventTypeName(eventType)}
                            </span>
                            ${event.extendedProps?.clientName || ''}
                        </div>
                        ${event.extendedProps?.notes ? `
                        <div class="agenda-item-notes">
                            <i class="fas fa-comment"></i> ${event.extendedProps.notes}
                        </div>` : ''}
                    </div>
                    <button class="btn-edit" data-event-id="${event.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                `;
                
                agendaList.appendChild(eventElement);
            });
        });
    
        // Agregar event listeners a los botones de edición
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                const eventId = btn.dataset.eventId;
                const event = events.find(e => e.id == eventId);
                if (event) openEventModal(event);
            });
        });
    }

    function initializeCalendar() {
        if (calendar) {
            calendar.destroy();
        }
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: 'obtener_eventos.php',
            eventDisplay: 'block',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            // Añade esta configuración:
            eventContent: function(arg) {
                const eventType = getEventTypeName(arg.event.extendedProps?.type || '');
                const clientName = arg.event.extendedProps?.clientName || '';
                
                return {
                    html: `
                        <div class="fc-event-title">${clientName}</div>
                        <div class="fc-event-type">${eventType}</div>
                    `
                };
            },
            dateClick: function(info) {
                createNewEvent(info.date);
            },
            eventClick: function(info) {
                console.log('Evento clickeado:', info.event);
                console.log('Tipo de evento:', info.event.extendedProps?.type);
                openEventModal(info.event);
            },
            windowResize: function(view) {
                calendar.updateSize();
            }
        });
    
        calendar.render();
        return calendar;
    }

    // ==================== EVENT LISTENERS ====================
    toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
    mobileMenuToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 992) sidebar.classList.remove('show');
        });
    });

    calendarViewBtn.addEventListener('click', () => {
        calendarContainer.style.display = 'block';
        agendaView.style.display = 'none';
        setTimeout(() => {
            initializeCalendar();
            loadEvents();
        }, 10);
    });
    
    agendaViewBtn.addEventListener('click', async () => {
        calendarContainer.style.display = 'none';
        agendaView.style.display = 'block';
        
        try {
            const events = await loadEvents();
            renderAgendaView(events);
        } catch (error) {
            console.error('Error al cargar eventos para agenda:', error);
        }
    });
    
    newEventBtn.addEventListener('click', () => {
        createNewEvent(new Date());
    });
    
    agendaFilter.addEventListener('change', () => loadEvents().then(renderAgendaView));
    
    eventForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const eventId = document.getElementById('event-id').value;
        const clientDni = eventClientSelect.value;
        const eventType = eventTypeSelect.value;
        const startDate = document.getElementById('event-start-date').value;
        const startTime = document.getElementById('event-start-time').value;
        const duration = document.getElementById('event-duration').value;
        const notes = document.getElementById('event-notes').value;
    
        // Validación adicional
        if (!clientDni || clientDni === 'Seleccionar cliente...') {
            Swal.fire('Error', 'Por favor seleccione un cliente válido', 'error');
            return;
        }
    
        if (!eventType || eventType === 'Seleccionar tipo...') {
            Swal.fire('Error', 'Por favor seleccione un tipo de evento válido', 'error');
            return;
        }
    
        const eventData = {
            clientId: clientDni,
            type: eventType,
            startDate: startDate,
            startTime: startTime,
            duration: parseInt(duration),
            notes: notes || '' // Asegura que notes nunca sea undefined
        };
    
        if (eventId) {
            eventData.eventId = eventId;
        }
    
        try {
            const result = await saveEvent(eventData);
            
            if (result.success) {
                await loadEvents();
                eventModal.style.display = 'none';
                Swal.fire('Éxito', eventId ? 'Evento actualizado' : 'Evento creado', 'success');
            } else {
                throw new Error(result.error || 'Error al guardar');
            }
        } catch (error) {
            console.error('Error al guardar:', error);
            Swal.fire('Error', error.message || 'Error al guardar el evento', 'error');
        }
    });

    deleteEventBtn.addEventListener('click', async function() {
        const eventId = document.getElementById('event-id').value;
        
        const { isConfirmed } = await Swal.fire({
            title: '¿Eliminar cita?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar'
        });
        
        if (isConfirmed) {
            try {
                const result = await deleteEvent(eventId);
                
                if (result.success) {
                    await loadEvents();
                    eventModal.style.display = 'none';
                    Swal.fire('Eliminado', 'Evento eliminado correctamente', 'success');
                } else {
                    throw new Error(result.error || 'Error al eliminar');
                }
            } catch (error) {
                Swal.fire('Error', error.message || 'Error al eliminar el evento', 'error');
            }
        }
    });

    cancelEventBtn.addEventListener('click', () => {
        eventModal.style.display = 'none';
    });

    // ==================== INICIALIZACIÓN ====================
    function initializeApp() {
        initializeEventTypeSelect();
        fetchClients();
        initializeCalendar();
        loadEvents();
    }

    // Iniciar la aplicación
    initializeApp();

    // Manejar redimensionamiento de ventana
    window.addEventListener('resize', function() {
        if (calendar && calendarContainer.style.display !== 'none') {
            setTimeout(() => {
                calendar.updateSize();
            }, 300);
        }
    });
});