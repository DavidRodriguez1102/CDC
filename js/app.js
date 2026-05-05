// Sistema de Gestión de Federaciones de Fútbol con API
class SoccerManagementSystem {
    constructor() {
        this.currentPage = 1;
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.checkAuth();
        await this.loadInitialData();
    }

    async loadInitialData() {
        // Cargar federaciones si estamos en la página correspondiente
        if (document.getElementById('federacionesTableBody')) {
            await this.loadFederaciones();
        }
        
        // Cargar equipos si estamos en el dashboard
        if (document.querySelector('.teams-grid')) {
            await this.loadEquipos();
        }

        // Cargar select de federaciones en formularios
        await this.loadFederacionesSelect();

        // Cargar select de equipos en formularios
        await this.loadEquiposSelect();

        // Cargar jugadores si estamos en la página correspondiente
        if (document.getElementById('jugadoresTableBody')) {
            await this.loadJugadores();
        }
    }

    async checkAuth() {
        const currentUser = api.getCurrentUser();
        const isLoginPage = window.location.pathname.includes('login.html');
        const isIndexPage = window.location.pathname.includes('index.html') || 
                          window.location.pathname === '/' ||
                          window.location.pathname.endsWith('/');

        if (!api.isAuthenticated() && !isLoginPage && !isIndexPage) {
            window.location.href = 'login.html';
        }
    }

    setupEventListeners() {
        // Login Form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Federación Form
        const federacionForm = document.getElementById('federacionForm');
        if (federacionForm) {
            federacionForm.addEventListener('submit', (e) => this.handleCreateFederacion(e));
        }

        // Equipo Form        const equipoForm = document.getElementById('equipoForm');
        if (equipoForm) {
            equipoForm.addEventListener('submit', (e) => this.handleCreateEquipo(e));
        }

        // Jugador Form
        const jugadorForm = document.getElementById('jugadorForm');
        if (jugadorForm) {
            jugadorForm.addEventListener('submit', (e) => this.handleCreateJugador(e));
        }

        // Modales
        this.setupModalHandlers();

        // Búsqueda
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        }
    }

    // === FUNCIONES DE AUTENTICACIÓN ===
    async handleLogin(e) {
        e.preventDefault();
        
        const userId = document.getElementById('userId').value.trim();
        const password = document.getElementById('password').value.trim();
        const submitBtn = e.target.querySelector('button[type="submit"]');

        try {
            // Deshabilitar botón y mostrar loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Iniciando sesión...';
            
            const response = await api.login(userId, password);
            
            // Redirigir al dashboard
            window.location.href = 'dashboard.html';
        } catch (error) {
            this.showError(error.message || 'Error al iniciar sesión');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Iniciar Sesión →';
        }
    }

    // === FUNCIONES DE FEDERACIONES ===
    async loadFederaciones(page = 1) {
        const limit = 10;
        try {
            const federaciones = await api.getFederaciones(page, limit);
            this.renderFederacionesTable(federaciones);
            this.updatePagination(page, federaciones.length < limit);
        } catch (error) {
            this.showError('Error al cargar federaciones: ' + error.message);
        }
    }

    renderFederacionesTable(federaciones) {
        const tbody = document.getElementById('federacionesTableBody');
        if (!tbody) return;

        tbody.innerHTML = federaciones.map(fed => `
            <tr>
                <td>${fed.nombre}</td>
                <td>${fed.id}</td>
                <td>${this.formatDate(fed.fecha_fundacion)}</td>
                <td>${fed.total_equipos || 0}</td>
                <td>
                    <button class="btn-icon" onclick="soccerSystem.editFederacion(${fed.id})" title="Editar">✏️</button>
                    <button class="btn-icon" onclick="soccerSystem.deleteFederacion(${fed.id})" title="Eliminar">🗑️</button>
                </td>
            </tr>
        `).join('');
    }

    async handleCreateFederacion(e) {
        e.preventDefault();
        
        const federacionData = {
            nombre: document.getElementById('fedNombre').value.trim(),
            fecha_fundacion: document.getElementById('fedFecha').value,
            departamento: document.getElementById('fedDepartamento').value.trim(),
            municipio: document.getElementById('fedMunicipio').value.trim(),
            complemento: document.getElementById('fedComplemento').value.trim()
        };

        // Validaciones
        if (!this.validateFederacion(federacionData)) {
            return;
        }

        try {
            await api.createFederacion(federacionData);
            this.closeModal('federacionModal');
            await this.loadFederaciones();
            this.showSuccess('Federación creada exitosamente!');
        } catch (error) {
            this.showError('Error al crear federación: ' + error.message);
        }
    }

    validateFederacion(data) {
        if (data.nombre.length < 3) {
            this.showError('El nombre debe tener al menos 3 caracteres.');
            return false;
        }

        if (!data.fecha_fundacion) {
            this.showError('La fecha de fundación es requerida.');
            return false;
        }

        const fecha = new Date(data.fecha_fundacion);
        if (fecha > new Date()) {
            this.showError('La fecha de fundación no puede ser futura.');
            return false;
        }

        if (data.departamento.length < 3) {
            this.showError('El departamento debe tener al menos 3 caracteres.');
            return false;
        }

        if (data.municipio.length < 3) {
            this.showError('El municipio debe tener al menos 3 caracteres.');
            return false;
        }

        return true;
    }

    async editFederacion(id) {
        try {
            const federacion = await api.getFederacion(id);
            
            document.getElementById('fedId').value = federacion.id;
            document.getElementById('fedNombre').value = federacion.nombre;
            document.getElementById('fedFecha').value = federacion.fecha_fundacion;
            document.getElementById('fedDepartamento').value = federacion.departamento;
            document.getElementById('fedMunicipio').value = federacion.municipio;
            document.getElementById('fedComplemento').value = federacion.complemento || '';
            
            this.openModal('federacionModal');
        } catch (error) {
            this.showError('Error al cargar federación: ' + error.message);
        }
    }

    async deleteFederacion(id) {
        if (!confirm('¿Está seguro de eliminar esta federación? Esta acción no se puede deshacer.')) {
            return;
        }

        try {
            await api.deleteFederacion(id);
            await this.loadFederaciones();
            this.showSuccess('Federación eliminada exitosamente.');
        } catch (error) {
            this.showError('Error al eliminar federación: ' + error.message);
        }
    }

    // === FUNCIONES DE EQUIPOS ===
    async loadEquipos(page = 1) {
        const limit = 10;
        try {
            const equipos = await api.getEquipos(null, page, limit);
            this.renderEquipos(equipos);
            this.updatePagination(page, equipos.length < limit);
        } catch (error) {
            this.showError('Error al cargar equipos: ' + error.message);
        }
    }

    renderEquipos(equipos) {
        const teamsGrid = document.querySelector('.teams-grid');
        if (teamsGrid) {
            teamsGrid.innerHTML = equipos.map(equipo => `
                <div class="team-card">
                    <div class="team-card-header">
                        <div class="team-logo">⚽</div>
                        <div class="team-info">
                            <h3>${equipo.nombre}</h3>
                            <span class="team-division">${equipo.federacion_nombre || ''}</span>
                        </div>
                        <span class="player-count">${equipo.total_jugadores || 0} Jugadores</span>
                    </div>
                    <div class="team-card-actions">
                        <button class="btn-icon" onclick="soccerSystem.editEquipo(${equipo.id})" title="Editar">✏️</button>
                        <button class="btn-icon" onclick="soccerSystem.deleteEquipo(${equipo.id})" title="Eliminar">🗑️</button>
                    </div>
                </div>
            `).join('');
        }

        const tbody = document.getElementById('equiposTableBody');
        if (tbody) {
            tbody.innerHTML = equipos.map(eq => `
                <tr>
                    <td>${eq.nombre}</td>
                    <td>${eq.federacion_nombre || ''}</td>
                    <td>${eq.total_jugadores || 0}</td>
                    <td>
                        <button class="btn-icon" onclick="soccerSystem.editEquipo(${eq.id})" title="Editar">✏️</button>
                        <button class="btn-icon" onclick="soccerSystem.deleteEquipo(${eq.id})" title="Eliminar">🗑️</button>
                    </td>
                </tr>
            `).join('');
        }
    }

    async loadFederacionesSelect() {
        const select = document.getElementById('federacionId');
        if (!select) return;

        try {
            const federaciones = await api.getFederaciones();
            
            select.innerHTML = '<option value="">Seleccionar federación</option>' +
                federaciones.map(fed => 
                    `<option value="${fed.id}">${fed.nombre}</option>`
                ).join('');
        } catch (error) {
            console.error('Error al cargar federaciones para select:', error);
        }
    }

    async loadEquiposSelect() {
        const select = document.getElementById('jugadorEquipo');
        if (!select) return;

        try {
            const equipos = await api.getEquipos();
            
            select.innerHTML = '<option value="">Seleccionar Equipo</option>' +
                equipos.map(eq => 
                    `<option value="${eq.id}">${eq.nombre}</option>`
                ).join('');
        } catch (error) {
            console.error('Error al cargar equipos para select:', error);
        }
    }

    async handleCreateEquipo(e) {
        e.preventDefault();
        
        const equipoData = {
            nombre: document.getElementById('equipoNombre').value.trim(),
            federacion_id: document.getElementById('federacionId').value
        };

        if (!equipoData.nombre || !equipoData.federacion_id) {
            this.showError('Todos los campos son requeridos.');
            return;
        }

        try {
            await api.createEquipo(equipoData);
            this.closeModal('equipoModal');
            await this.loadEquipos();
            this.showSuccess('Equipo creado exitosamente!');
        } catch (error) {
            this.showError('Error al crear equipo: ' + error.message);
        }
    }

    async deleteEquipo(id) {
        if (!confirm('¿Está seguro de eliminar este equipo?')) {
            return;
        }

        try {
            await api.deleteEquipo(id);
            await this.loadEquipos();
            this.showSuccess('Equipo eliminado exitosamente.');
        } catch (error) {
            this.showError('Error al eliminar equipo: ' + error.message);
        }
    }

    // === FUNCIONES DE JUGADORES ===
    async loadJugadores(page = 1) {
        const limit = 10;
        try {
            const jugadores = await api.getJugadores(null, page, limit);
            this.renderJugadoresTable(jugadores);
            this.updatePagination(page, jugadores.length < limit);
        } catch (error) {
            this.showError('Error al cargar jugadores: ' + error.message);
        }
    }

    renderJugadoresTable(jugadores) {
        const tbody = document.getElementById('jugadoresTableBody');
        if (!tbody) return;

        tbody.innerHTML = jugadores.map(jug => `
            <tr>
                <td>${jug.nombre}</td>
                <td>${this.formatDate(jug.fecha_nacimiento)}</td>
                <td>${jug.genero}</td>
                <td>${jug.equipo_nombre || ''}</td>
                <td>
                    <button class="btn-icon" onclick="soccerSystem.editJugador(${jug.id})" title="Editar">✏️</button>
                    <button class="btn-icon" onclick="soccerSystem.deleteJugador(${jug.id})" title="Eliminar">🗑️</button>
                </td>
            </tr>
        `).join('');
    }

    updatePagination(page, isLast) {
        this.currentPage = page;
        const pageInfo = document.getElementById('pageInfo');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        if (pageInfo) pageInfo.textContent = `Página ${page}`;
        if (prevBtn) prevBtn.disabled = page === 1;
        if (nextBtn) nextBtn.disabled = isLast;
    }

    async handleCreateJugador(e) {
        e.preventDefault();
        
        const jugadorData = {
            nombre: document.getElementById('jugadorNombre').value.trim(),
            fecha_nacimiento: document.getElementById('jugadorFechaNacimiento').value,
            genero: document.getElementById('jugadorGenero').value,
            equipo_id: parseInt(document.getElementById('jugadorEquipo').value)
        };

        if (!jugadorData.nombre || !jugadorData.fecha_nacimiento || 
            !jugadorData.genero || !jugadorData.equipo_id) {
            this.showError('Todos los campos son requeridos.');
            return;
        }

        try {
            await api.createJugador(jugadorData);
            this.closeModal('jugadorModal');
            await this.loadJugadores();
            this.showSuccess('Jugador registrado exitosamente!');
        } catch (error) {
            this.showError('Error al registrar jugador: ' + error.message);
        }
    }

    async editJugador(id) {
        try {
            const jugador = await api.getJugador(id);
            
            document.getElementById('jugadorNombre').value = jugador.nombre;
            document.getElementById('jugadorFechaNacimiento').value = jugador.fecha_nacimiento;
            document.getElementById('jugadorGenero').value = jugador.genero;
            document.getElementById('jugadorEquipo').value = jugador.equipo_id;
            
            this.openModal('jugadorModal');
        } catch (error) {
            this.showError('Error al cargar jugador: ' + error.message);
        }
    }

    async deleteJugador(id) {
        if (!confirm('¿Está seguro de eliminar este jugador?')) {
            return;
        }

        try {
            await api.deleteJugador(id);
            await this.loadJugadores();
            this.showSuccess('Jugador eliminado exitosamente.');
        } catch (error) {
            this.showError('Error al eliminar jugador: ' + error.message);
        }
    }

    // === FUNCIONES DE BÚSQUEDA ===
    async handleSearch(query) {
        if (query.length < 2) {
            await this.loadFederaciones();
            return;
        }

        try {
            const federaciones = await api.getFederaciones();
            const filtered = federaciones.filter(fed => 
                fed.nombre.toLowerCase().includes(query.toLowerCase()) ||
                fed.departamento.toLowerCase().includes(query.toLowerCase()) ||
                fed.municipio.toLowerCase().includes(query.toLowerCase())
            );
            this.renderFederacionesTable(filtered);
        } catch (error) {
            console.error('Error en búsqueda:', error);
        }
    }

    // === FUNCIONES DE UI ===
    showError(message) {
        const errorDiv = document.getElementById('errorMessage') || document.getElementById('notificationArea');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            errorDiv.className = 'notification error';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        } else {
            alert(message);
        }
    }

    showSuccess(message) {
        const successDiv = document.getElementById('notificationArea');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            successDiv.className = 'notification success';
            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 3000);
        } else {
            alert(message);
        }
    }

    setupModalHandlers() {
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.onclick = function() {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            };
        });

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    formatDate(dateString) {
        const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    }
}

// Inicializar el sistema
const soccerSystem = new SoccerManagementSystem();

// Funciones globales
function nuevaFederacion() {
    document.getElementById('federacionForm').reset();
    document.getElementById('fedId').value = '';
    soccerSystem.openModal('federacionModal');
}

function inscribirEquipo() {
    document.getElementById('equipoForm').reset();
    soccerSystem.openModal('equipoModal');
}

function nuevoEquipo() {
    document.getElementById('equipoForm').reset();
    document.getElementById('equipoId').value = '';
    soccerSystem.openModal('equipoModal');
}

function registrarJugador() {
    document.getElementById('jugadorForm').reset();
    soccerSystem.openModal('jugadorModal');
}

function nuevoJugador() {
    document.getElementById('jugadorForm').reset();
    soccerSystem.openModal('jugadorModal');
}

function changePage(delta) {
    const newPage = soccerSystem.currentPage + delta;
    if (newPage < 1) return;
    if (document.getElementById('federacionesTableBody')) {
        soccerSystem.loadFederaciones(newPage);
    } else if (document.getElementById('equiposTableBody')) {
        soccerSystem.loadEquipos(newPage);
    } else if (document.getElementById('jugadoresTableBody')) {
        soccerSystem.loadJugadores(newPage);
    }
}

function cerrarSesion() {
    if (confirm('¿Está seguro de cerrar sesión?')) {
        api.logout();
    }
}