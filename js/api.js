// Cliente API para el sistema de gestión de fútbol
class SoccerAPI {
    constructor() {
        this.baseURL = 'http://localhost:8000/config';
        this.token = localStorage.getItem('auth_token');
    }

    // Headers comunes
    getHeaders() {
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        return headers;
    }

    // Manejar respuestas
    async handleResponse(response) {
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Error en la solicitud');
        }
        
        return data;
    }

    // === AUTENTICACIÓN ===
    async login(username, password) {
        try {
            const response = await fetch(`${this.baseURL}/auth.php`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({ username, password })
            });
            
            const data = await this.handleResponse(response);
            
            if (data.token) {
                this.token = data.token;
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
            }
            
            return data;
        } catch (error) {
            throw error;
        }
    }

    logout() {
        this.token = null;
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        window.location.href = 'index.html';
    }

    isAuthenticated() {
        return !!this.token;
    }

    getCurrentUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }

    // === FEDERACIONES ===
    async getFederaciones(page = 1, limit = 10) {
        let url = `${this.baseURL}/federaciones.php?page=${page}&limit=${limit}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: this.getHeaders()
        });
        return this.handleResponse(response);
    }

    async getFederacion(id) {
        try {
            const response = await fetch(`${this.baseURL}/federaciones.php?id=${id}`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    async createFederacion(federacionData) {
        try {
            const response = await fetch(`${this.baseURL}/federaciones.php`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify(federacionData)
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    async updateFederacion(id, federacionData) {
        try {
            const response = await fetch(`${this.baseURL}/federaciones.php`, {
                method: 'PUT',
                headers: this.getHeaders(),
                body: JSON.stringify({ id, ...federacionData })
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    async deleteFederacion(id) {
        try {
            const response = await fetch(`${this.baseURL}/federaciones.php`, {
                method: 'DELETE',
                headers: this.getHeaders(),
                body: JSON.stringify({ id })
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    // === EQUIPOS ===
    async getEquipos(federacionId = null, page = 1, limit = 10) {
        let url = `${this.baseURL}/equipos.php?page=${page}&limit=${limit}`;
        if (federacionId) {
            url += `&federacion_id=${federacionId}`;
        }
        
        const response = await fetch(url, {
            method: 'GET',
            headers: this.getHeaders()
        });
        return this.handleResponse(response);
    }

    async getEquipo(id) {
        try {
            const response = await fetch(`${this.baseURL}/equipos.php?id=${id}`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    async createEquipo(equipoData) {
        try {
            const response = await fetch(`${this.baseURL}/equipos.php`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify(equipoData)
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    async updateEquipo(id, equipoData) {
        try {
            const response = await fetch(`${this.baseURL}/equipos.php`, {
                method: 'PUT',
                headers: this.getHeaders(),
                body: JSON.stringify({ id, ...equipoData })
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    async deleteEquipo(id) {
        try {
            const response = await fetch(`${this.baseURL}/equipos.php`, {
                method: 'DELETE',
                headers: this.getHeaders(),
                body: JSON.stringify({ id })
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    // === JUGADORES ===
    async getJugadores(equipoId = null, page = 1, limit = 10) {
        let url = `${this.baseURL}/jugadores.php?page=${page}&limit=${limit}`;
        if (equipoId) {
            url += `&equipo_id=${equipoId}`;
        }
        
        const response = await fetch(url, {
            method: 'GET',
            headers: this.getHeaders()
        });
        return this.handleResponse(response);
    }

    async getJugador(id) {
        try {
            const response = await fetch(`${this.baseURL}/jugadores.php?id=${id}`, {
                method: 'GET',
                headers: this.getHeaders()
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    async createJugador(jugadorData) {
        try {
            const response = await fetch(`${this.baseURL}/jugadores.php`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify(jugadorData)
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    async updateJugador(id, jugadorData) {
        try {
            const response = await fetch(`${this.baseURL}/jugadores.php`, {
                method: 'PUT',
                headers: this.getHeaders(),
                body: JSON.stringify({ id, ...jugadorData })
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    async deleteJugador(id) {
        try {
            const response = await fetch(`${this.baseURL}/jugadores.php`, {
                method: 'DELETE',
                headers: this.getHeaders(),
                body: JSON.stringify({ id })
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }
}

// Crear instancia global
const api = new SoccerAPI();