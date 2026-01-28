<div class="wrap rt-admin-wrapper">
    <h1 class="wp-heading-inline">Dashboard - Reservas Terapia</h1>

    <div class="rt-dashboard-widgets">
        <!-- Stats Cards -->
        <div class="rt-card rt-stats-card">
            <h3>Reservas Hoy</h3>
            <div class="rt-stat-number" id="rt-stats-today">0</div>
        </div>
        <div class="rt-card rt-stats-card">
            <h3>Reservas Semana</h3>
            <div class="rt-stat-number" id="rt-stats-week">0</div>
        </div>
        <div class="rt-card rt-stats-card">
            <h3>Reservas Mes</h3>
            <div class="rt-stat-number" id="rt-stats-month">0</div>
        </div>
        <div class="rt-card rt-stats-card">
            <h3>Tasa Cancelación</h3>
            <div class="rt-stat-number" id="rt-stats-cancellation">0%</div>
        </div>
    </div>

    <div class="rt-dashboard-charts">
        <div class="rt-card">
            <h3>Actividad (Últimos 30 días)</h3>
            <canvas id="rt-bookings-chart"></canvas>
        </div>
    </div>

    <div class="rt-dashboard-lists">
        <div class="rt-card">
            <h3>Próximas Reservas</h3>
            <table class="wp-list-table widefat fixed striped" id="rt-upcoming-bookings">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4">Cargando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="rt-card">
            <h3>Últimas Reservas Creadas</h3>
            <table class="wp-list-table widefat fixed striped" id="rt-recent-bookings">
                <thead>
                    <tr>
                        <th>Creado</th>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4">Cargando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>