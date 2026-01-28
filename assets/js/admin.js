/**
 * D.Calendar - Admin JavaScript
 * Handles all admin panel interactivity and AJAX operations
 *
 * @package    DCalendar
 * @since      1.0.0
 */

(function ($) {
	'use strict';

	/**
	 * Main Admin Module
	 */
	const RTAdmin = {
		/**
		 * Cached elements
		 */
		cache: {},

		/**
		 * Chart instance
		 */
		chart: null,

		/**
		 * Current page state
		 */
		state: {
			currentPage: 1,
			editingId: null,
		},

		/**
		 * Initialize the admin module
		 */
		init: function () {
			this.cacheElements();
			this.bindEvents();
			this.initPage();
		},

		/**
		 * Cache DOM elements
		 */
		cacheElements: function () {
			this.cache.$body = $('body');
			this.cache.$modals = $('.rt-modal');
			this.cache.$closeModal = $('.rt-close-modal, .rt-close-modal-btn');
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function () {
			// Modal events
			this.cache.$closeModal.on('click', this.closeModal.bind(this));
			this.cache.$modals.on('click', this.handleModalBackdrop.bind(this));
			$(document).on('keydown', this.handleEscape.bind(this));

			// Services events
			$('#rt-btn-add-service').on('click', this.openAddService.bind(this));
			$(document).on('click', '.rt-edit-service', this.openEditService.bind(this));
			$(document).on('click', '.rt-delete-service', this.deleteService.bind(this));
			$('#rt-service-form').on('submit', this.saveService.bind(this));

			// Schedules events
			$(document).on('change', '.rt-day-toggle', this.toggleDayInputs.bind(this));
			$(document).on('change', '.rt-time-start, .rt-time-end, .rt-slot-duration', this.previewSlots.bind(this));
			$('#rt-schedule-form').on('submit', this.saveSchedules.bind(this));

			// Bookings events
			$('#rt-btn-add-booking').on('click', this.openAddBooking.bind(this));
			$(document).on('click', '.rt-edit-booking', this.openEditBooking.bind(this));
			$(document).on('click', '.rt-delete-booking', this.deleteBooking.bind(this));
			$('#rt-booking-form').on('submit', this.saveBooking.bind(this));
			$('#rt-apply-filters').on('click', this.filterBookings.bind(this));
			$(document).on('click', '.rt-change-status', this.changeBookingStatus.bind(this));
			$(document).on('click', '.rt-page-btn', this.handlePagination.bind(this));

			// Alerts events
			$(document).on('click', '.rt-btn-expand-alert', this.toggleAlertConfig.bind(this));
			$(document).on('change', '.rt-alert-toggle', this.toggleAlertActive.bind(this));
			$('#rt-save-alerts').on('click', this.saveAlerts.bind(this));
			$(document).on('click', '.rt-preview-alert', this.previewAlert.bind(this));

			// Integrations events
			$('#rt-test-twilio').on('click', this.testTwilio.bind(this));
			$('#rt-save-zoom').on('click', this.saveZoom.bind(this));
		},

		/**
		 * Initialize current page
		 */
		initPage: function () {
			const page = this.getCurrentPage();

			switch (page) {
				case 'dashboard':
					this.initDashboard();
					break;
				case 'services':
					this.loadServices();
					this.initColorPicker();
					break;
				case 'schedules':
					this.loadSchedules();
					break;
				case 'bookings':
					this.loadBookings();
					this.loadServicesDropdown();
					break;
				case 'alerts':
					this.loadAlerts();
					break;
			}
		},

		/**
		 * Get current admin page
		 */
		getCurrentPage: function () {
			const urlParams = new URLSearchParams(window.location.search);
			const page = urlParams.get('page') || '';

			if (page === 'd-calendar') return 'dashboard';
			if (page === 'rt-services') return 'services';
			if (page === 'rt-schedules') return 'schedules';
			if (page === 'rt-bookings') return 'bookings';
			if (page === 'rt-alerts') return 'alerts';
			if (page === 'rt-integrations') return 'integrations';

			return '';
		},

		/**
		 * Initialize color picker
		 */
		initColorPicker: function () {
			if ($.fn.wpColorPicker) {
				$('.rt-color-field').wpColorPicker();
			}
		},

		// =====================================================================
		// AJAX Helpers
		// =====================================================================

		/**
		 * Make AJAX request
		 */
		ajax: function (action, data, callback) {
			const requestData = $.extend({
				action: action,
				nonce: rtAdmin.nonce,
			}, data);

			$.ajax({
				url: rtAdmin.ajaxUrl,
				type: 'POST',
				data: requestData,
				success: function (response) {
					if (callback) callback(response);
				},
				error: function (xhr, status, error) {
					console.error('AJAX Error:', error);
					RTAdmin.showNotice('error', rtAdmin.strings.error);
				}
			});
		},

		/**
		 * Show notification
		 */
		showNotice: function (type, message) {
			const $notice = $('<div class="rt-notice rt-notice-' + type + '">' + message + '</div>');
			$('.rt-admin-wrapper h1').after($notice);

			setTimeout(function () {
				$notice.fadeOut(300, function () {
					$(this).remove();
				});
			}, 4000);
		},

		// =====================================================================
		// Modal Handlers
		// =====================================================================

		/**
		 * Open modal
		 */
		openModal: function (modalId) {
			$(modalId).fadeIn(200);
			this.cache.$body.css('overflow', 'hidden');
		},

		/**
		 * Close modal
		 */
		closeModal: function () {
			$('.rt-modal').fadeOut(200);
			this.cache.$body.css('overflow', '');
			this.state.editingId = null;
		},

		/**
		 * Handle modal backdrop click
		 */
		handleModalBackdrop: function (e) {
			if ($(e.target).hasClass('rt-modal')) {
				this.closeModal();
			}
		},

		/**
		 * Handle escape key
		 */
		handleEscape: function (e) {
			if (e.key === 'Escape') {
				this.closeModal();
			}
		},

		// =====================================================================
		// Dashboard
		// =====================================================================

		/**
		 * Initialize dashboard
		 */
		initDashboard: function () {
			this.loadDashboardStats();
			this.loadUpcomingBookings();
			this.loadRecentBookings();
		},

		/**
		 * Load dashboard statistics
		 */
		loadDashboardStats: function () {
			this.ajax('rt_get_dashboard_stats', {}, function (response) {
				if (response.success) {
					const data = response.data;
					$('#rt-stats-today').text(data.today);
					$('#rt-stats-week').text(data.week);
					$('#rt-stats-month').text(data.month);
					$('#rt-stats-cancellation').text(data.cancellation_rate + '%');

					if (data.cancellation_rate > 20) {
						$('#rt-stats-cancellation').addClass('negative');
					}

					RTAdmin.initChart(data.chart_data);
				}
			});
		},

		/**
		 * Initialize chart
		 */
		initChart: function (chartData) {
			const ctx = document.getElementById('rt-bookings-chart');
			if (!ctx) return;

			// Prepare data for last 30 days
			const labels = [];
			const values = [];
			const today = new Date();

			for (let i = 29; i >= 0; i--) {
				const date = new Date(today);
				date.setDate(date.getDate() - i);
				const dateStr = date.toISOString().split('T')[0];
				labels.push(date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }));

				const found = chartData.find(d => d.date === dateStr);
				values.push(found ? parseInt(found.count) : 0);
			}

			if (this.chart) {
				this.chart.destroy();
			}

			this.chart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: labels,
					datasets: [{
						label: 'Reservas',
						data: values,
						borderColor: '#3b82f6',
						backgroundColor: 'rgba(59, 130, 246, 0.1)',
						fill: true,
						tension: 0.3,
						pointRadius: 3,
						pointHoverRadius: 6,
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								stepSize: 1
							}
						},
						x: {
							ticks: {
								maxRotation: 45,
								minRotation: 45
							}
						}
					}
				}
			});
		},

		/**
		 * Load upcoming bookings
		 */
		loadUpcomingBookings: function () {
			this.ajax('rt_get_upcoming_bookings', {}, function (response) {
				if (response.success) {
					RTAdmin.renderUpcomingBookings(response.data);
				}
			});
		},

		/**
		 * Render upcoming bookings table
		 */
		renderUpcomingBookings: function (bookings) {
			const $tbody = $('#rt-upcoming-bookings tbody');
			$tbody.empty();

			if (!bookings.length) {
				$tbody.html('<tr><td colspan="4">' + rtAdmin.strings.noResults + '</td></tr>');
				return;
			}

			bookings.forEach(function (booking) {
				const statusClass = 'status-' + booking.estado;
				const row = `
                    <tr>
                        <td>${RTAdmin.formatDate(booking.fecha)} ${booking.hora}</td>
                        <td>${RTAdmin.escapeHtml(booking.cliente_nombre)}</td>
                        <td>${RTAdmin.escapeHtml(booking.servicio_nombre || '-')}</td>
                        <td><span class="rt-status-badge ${booking.estado}">${booking.estado}</span></td>
                    </tr>
                `;
				$tbody.append(row);
			});
		},

		/**
		 * Load recent bookings
		 */
		loadRecentBookings: function () {
			this.ajax('rt_get_recent_bookings', {}, function (response) {
				if (response.success) {
					RTAdmin.renderRecentBookings(response.data);
				}
			});
		},

		/**
		 * Render recent bookings table
		 */
		renderRecentBookings: function (bookings) {
			const $tbody = $('#rt-recent-bookings tbody');
			$tbody.empty();

			if (!bookings.length) {
				$tbody.html('<tr><td colspan="4">' + rtAdmin.strings.noResults + '</td></tr>');
				return;
			}

			bookings.forEach(function (booking) {
				const row = `
                    <tr>
                        <td>${RTAdmin.formatDateTime(booking.created_at)}</td>
                        <td>${RTAdmin.escapeHtml(booking.cliente_nombre)}</td>
                        <td>${RTAdmin.escapeHtml(booking.servicio_nombre || '-')}</td>
                        <td><span class="rt-status-badge ${booking.estado}">${booking.estado}</span></td>
                    </tr>
                `;
				$tbody.append(row);
			});
		},

		// =====================================================================
		// Services CRUD
		// =====================================================================

		/**
		 * Load services list
		 */
		loadServices: function () {
			const $tbody = $('#rt-services-table-body');
			$tbody.html('<tr><td colspan="5">' + rtAdmin.strings.loading + '</td></tr>');

			this.ajax('rt_get_services', {}, function (response) {
				if (response.success) {
					RTAdmin.renderServices(response.data);
				}
			});
		},

		/**
		 * Render services table
		 */
		renderServices: function (services) {
			const $tbody = $('#rt-services-table-body');
			$tbody.empty();

			if (!services.length) {
				$tbody.html('<tr><td colspan="5">' + rtAdmin.strings.noResults + '</td></tr>');
				return;
			}

			services.forEach(function (service) {
				const status = service.activo == 1 ? 'active' : 'inactive';
				const statusText = service.activo == 1 ? 'Activo' : 'Inactivo';
				const row = `
                    <tr data-id="${service.id}">
                        <td>
                            <span class="service-color" style="background-color: ${service.color}"></span>
                            ${RTAdmin.escapeHtml(service.nombre)}
                        </td>
                        <td>${service.duracion} min</td>
                        <td>$${parseFloat(service.precio).toFixed(2)}</td>
                        <td><span class="rt-status-badge ${status}">${statusText}</span></td>
                        <td>
                            <a href="#" class="rt-edit-service" data-id="${service.id}">Editar</a> | 
                            <a href="#" class="rt-delete-service" data-id="${service.id}" style="color:#ef4444">Eliminar</a>
                        </td>
                    </tr>
                `;
				$tbody.append(row);
			});
		},

		/**
		 * Open add service modal
		 */
		openAddService: function (e) {
			e.preventDefault();
			this.state.editingId = null;
			$('#rt-modal-title').text('Añadir Servicio');
			$('#rt-service-form')[0].reset();
			$('#service_id').val('');

			// Reset color picker
			if ($.fn.wpColorPicker) {
				$('#service_color').wpColorPicker('color', '#3b82f6');
			}

			this.openModal('#rt-service-modal');
		},

		/**
		 * Open edit service modal
		 */
		openEditService: function (e) {
			e.preventDefault();
			const id = $(e.currentTarget).data('id');

			this.ajax('rt_get_service', { id: id }, function (response) {
				if (response.success) {
					const service = response.data;
					RTAdmin.state.editingId = service.id;

					$('#rt-modal-title').text('Editar Servicio');
					$('#service_id').val(service.id);
					$('#service_name').val(service.nombre);
					$('#service_description').val(service.descripcion);
					$('#service_duration').val(service.duracion);
					$('#service_price').val(service.precio);
					$('#service_buffer_before').val(service.buffer_antes);
					$('#service_buffer_after').val(service.buffer_despues);
					$('#service_active').val(service.activo);

					if ($.fn.wpColorPicker) {
						$('#service_color').wpColorPicker('color', service.color);
					} else {
						$('#service_color').val(service.color);
					}

					RTAdmin.openModal('#rt-service-modal');
				}
			});
		},

		/**
		 * Save service
		 */
		saveService: function (e) {
			e.preventDefault();

			const $form = $('#rt-service-form');
			const $btn = $('#rt-save-service');
			const $spinner = $form.find('.spinner');

			$btn.prop('disabled', true);
			$spinner.addClass('is-active');

			const data = {
				service_id: $('#service_id').val(),
				service_name: $('#service_name').val(),
				service_description: $('#service_description').val(),
				service_duration: $('#service_duration').val(),
				service_price: $('#service_price').val(),
				service_color: $('#service_color').val(),
				service_buffer_before: $('#service_buffer_before').val(),
				service_buffer_after: $('#service_buffer_after').val(),
				service_active: $('#service_active').val(),
			};

			this.ajax('rt_save_service', data, function (response) {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');

				if (response.success) {
					RTAdmin.closeModal();
					RTAdmin.showNotice('success', response.data.message);
					RTAdmin.loadServices();
				} else {
					RTAdmin.showNotice('error', response.data.message);
				}
			});
		},

		/**
		 * Delete service
		 */
		deleteService: function (e) {
			e.preventDefault();

			if (!confirm(rtAdmin.strings.confirmDelete)) {
				return;
			}

			const id = $(e.currentTarget).data('id');

			this.ajax('rt_delete_service', { id: id }, function (response) {
				if (response.success) {
					RTAdmin.showNotice('success', response.data.message);
					RTAdmin.loadServices();
				} else {
					RTAdmin.showNotice('error', response.data.message);
				}
			});
		},

		// =====================================================================
		// Schedules
		// =====================================================================

		/**
		 * Load schedules configuration
		 */
		loadSchedules: function () {
			this.ajax('rt_get_schedules', {}, function (response) {
				if (response.success) {
					RTAdmin.populateSchedules(response.data);
				}
			});
		},

		/**
		 * Populate schedules form
		 */
		populateSchedules: function (schedules) {
			const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

			days.forEach(function (day) {
				if (schedules[day]) {
					const $row = $(`.rt-schedule-row[data-day="${day}"]`);
					const config = schedules[day];

					$row.find('.rt-day-toggle').prop('checked', config.active);
					$row.find('.rt-time-start').val(config.start).prop('disabled', !config.active);
					$row.find('.rt-time-end').val(config.end).prop('disabled', !config.active);
					$row.find('.rt-slot-duration').val(config.slot_duration).prop('disabled', !config.active);
				}
			});
		},

		/**
		 * Toggle day inputs
		 */
		toggleDayInputs: function (e) {
			const $toggle = $(e.target);
			const $row = $toggle.closest('.rt-schedule-row');
			const isActive = $toggle.is(':checked');

			$row.find('.rt-time-start, .rt-time-end, .rt-slot-duration').prop('disabled', !isActive);

			if (isActive) {
				this.previewSlots(e);
			}
		},

		/**
		 * Preview slots for selected day
		 */
		previewSlots: function (e) {
			const $row = $(e.target).closest('.rt-schedule-row');
			const start = $row.find('.rt-time-start').val();
			const end = $row.find('.rt-time-end').val();
			const duration = $row.find('.rt-slot-duration').val();

			if (!start || !end) return;

			this.ajax('rt_preview_slots', {
				start: start,
				end: end,
				slot_duration: duration
			}, function (response) {
				if (response.success) {
					const $container = $('#rt-slots-preview-container');
					$container.empty();

					response.data.forEach(function (slot) {
						$container.append('<span class="rt-slot-preview">' + slot + '</span>');
					});
				}
			});
		},

		/**
		 * Save schedules
		 */
		saveSchedules: function (e) {
			e.preventDefault();

			const $btn = $('#rt-save-schedule');
			const $spinner = $btn.siblings('.spinner');

			$btn.prop('disabled', true);
			$spinner.addClass('is-active');

			const formData = $('#rt-schedule-form').serializeArray();
			const data = { schedule: {} };

			formData.forEach(function (item) {
				const match = item.name.match(/schedule\[(\w+)\]\[(\w+)\]/);
				if (match) {
					const day = match[1];
					const field = match[2];
					if (!data.schedule[day]) data.schedule[day] = {};
					data.schedule[day][field] = item.value;
				}
			});

			this.ajax('rt_save_schedules', data, function (response) {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');

				if (response.success) {
					RTAdmin.showNotice('success', response.data.message);
				} else {
					RTAdmin.showNotice('error', response.data.message);
				}
			});
		},

		// =====================================================================
		// Bookings CRUD
		// =====================================================================

		/**
		 * Load bookings list
		 */
		loadBookings: function (page) {
			page = page || 1;
			this.state.currentPage = page;

			const $tbody = $('#rt-bookings-table-body');
			$tbody.html('<tr><td colspan="8">' + rtAdmin.strings.loading + '</td></tr>');

			const data = {
				page: page,
				service_id: $('#rt-filter-service').val(),
				status: $('#rt-filter-status').val(),
				date: $('#rt-filter-date').val(),
				search: $('#rt-filter-search').val(),
			};

			this.ajax('rt_get_bookings', data, function (response) {
				if (response.success) {
					RTAdmin.renderBookings(response.data.bookings);
					RTAdmin.renderPagination(response.data);
					$('#rt-total-items').text(response.data.total + ' elementos');
				}
			});
		},

		/**
		 * Render bookings table
		 */
		renderBookings: function (bookings) {
			const $tbody = $('#rt-bookings-table-body');
			$tbody.empty();

			if (!bookings.length) {
				$tbody.html('<tr><td colspan="8">' + rtAdmin.strings.noResults + '</td></tr>');
				return;
			}

			bookings.forEach(function (booking) {
				const row = `
                    <tr data-id="${booking.id}">
                        <td class="check-column"><input type="checkbox" value="${booking.id}"></td>
                        <td data-label="Código"><strong>${RTAdmin.escapeHtml(booking.codigo)}</strong></td>
                        <td data-label="Cliente">${RTAdmin.escapeHtml(booking.cliente_nombre)}</td>
                        <td data-label="Contacto">
                            ${RTAdmin.escapeHtml(booking.cliente_email)}<br>
                            <small>${RTAdmin.escapeHtml(booking.cliente_telefono || '')}</small>
                        </td>
                        <td data-label="Servicio">${RTAdmin.escapeHtml(booking.servicio_nombre || '-')}</td>
                        <td data-label="Fecha/Hora">${RTAdmin.formatDate(booking.fecha)} <br><strong>${booking.hora}</strong></td>
                        <td data-label="Estado"><span class="rt-status-badge ${booking.estado}">${booking.estado}</span></td>
                        <td>
                            <a href="#" class="rt-edit-booking" data-id="${booking.id}">Ver</a> | 
                            <a href="#" class="rt-delete-booking" data-id="${booking.id}" style="color:#ef4444">Eliminar</a>
                        </td>
                    </tr>
                `;
				$tbody.append(row);
			});
		},

		/**
		 * Render pagination
		 */
		renderPagination: function (data) {
			let $pagination = $('.rt-pagination');

			if (!$pagination.length) {
				$pagination = $('<div class="rt-pagination"></div>');
				$('#rt-bookings-table-body').closest('table').after($pagination);
			}

			$pagination.empty();

			if (data.pages <= 1) return;

			// Previous button
			const prevDisabled = data.current_page <= 1 ? 'disabled' : '';
			$pagination.append(`<button class="button rt-page-btn" data-page="${data.current_page - 1}" ${prevDisabled}>« Anterior</button>`);

			// Page info
			$pagination.append(`<span class="current-page">Página ${data.current_page} de ${data.pages}</span>`);

			// Next button
			const nextDisabled = data.current_page >= data.pages ? 'disabled' : '';
			$pagination.append(`<button class="button rt-page-btn" data-page="${data.current_page + 1}" ${nextDisabled}>Siguiente »</button>`);
		},

		/**
		 * Handle pagination click
		 */
		handlePagination: function (e) {
			e.preventDefault();
			const page = $(e.currentTarget).data('page');
			this.loadBookings(page);
		},

		/**
		 * Filter bookings
		 */
		filterBookings: function (e) {
			e.preventDefault();
			this.loadBookings(1);
		},

		/**
		 * Load services dropdown for filters
		 */
		loadServicesDropdown: function () {
			this.ajax('rt_get_services', {}, function (response) {
				if (response.success) {
					const $select = $('#rt-filter-service, #booking_service_id');

					response.data.forEach(function (service) {
						$select.append(`<option value="${service.id}">${RTAdmin.escapeHtml(service.nombre)}</option>`);
					});
				}
			});
		},

		/**
		 * Open add booking modal
		 */
		openAddBooking: function (e) {
			e.preventDefault();
			this.state.editingId = null;
			$('#rt-booking-modal-title').text('Nueva Reserva');
			$('#rt-booking-form')[0].reset();
			$('#booking_id').val('');
			this.openModal('#rt-booking-modal');
		},

		/**
		 * Open edit booking modal
		 */
		openEditBooking: function (e) {
			e.preventDefault();
			const id = $(e.currentTarget).data('id');

			this.ajax('rt_get_booking', { id: id }, function (response) {
				if (response.success) {
					const booking = response.data;
					RTAdmin.state.editingId = booking.id;

					$('#rt-booking-modal-title').text('Detalles de Reserva #' + booking.codigo);
					$('#booking_id').val(booking.id);
					$('#booking_client_name').val(booking.cliente_nombre);
					$('#booking_client_email').val(booking.cliente_email);
					$('#booking_client_phone').val(booking.cliente_telefono);
					$('#booking_service_id').val(booking.servicio_id);
					$('#booking_date').val(booking.fecha);
					$('#booking_time').val(booking.hora);
					$('#booking_status').val(booking.estado);
					$('#booking_notes').val(booking.notas);

					RTAdmin.openModal('#rt-booking-modal');
				}
			});
		},

		/**
		 * Save booking
		 */
		saveBooking: function (e) {
			e.preventDefault();

			const $form = $('#rt-booking-form');
			const $btn = $form.find('.button-primary');

			$btn.prop('disabled', true).text(rtAdmin.strings.saving);

			const data = {
				booking_id: $('#booking_id').val(),
				client_name: $('#booking_client_name').val(),
				client_email: $('#booking_client_email').val(),
				client_phone: $('#booking_client_phone').val(),
				service_id: $('#booking_service_id').val(),
				booking_date: $('#booking_date').val(),
				booking_time: $('#booking_time').val(),
				booking_status: $('#booking_status').val(),
				booking_notes: $('#booking_notes').val(),
			};

			this.ajax('rt_save_booking', data, function (response) {
				$btn.prop('disabled', false).text('Guardar Cambios');

				if (response.success) {
					RTAdmin.closeModal();
					RTAdmin.showNotice('success', response.data.message);
					RTAdmin.loadBookings(RTAdmin.state.currentPage);
				} else {
					RTAdmin.showNotice('error', response.data.message);
				}
			});
		},

		/**
		 * Delete booking
		 */
		deleteBooking: function (e) {
			e.preventDefault();

			if (!confirm(rtAdmin.strings.confirmDelete)) {
				return;
			}

			const id = $(e.currentTarget).data('id');

			this.ajax('rt_delete_booking', { id: id }, function (response) {
				if (response.success) {
					RTAdmin.showNotice('success', response.data.message);
					RTAdmin.loadBookings(RTAdmin.state.currentPage);
				} else {
					RTAdmin.showNotice('error', response.data.message);
				}
			});
		},

		/**
		 * Change booking status
		 */
		changeBookingStatus: function (e) {
			e.preventDefault();
			const $btn = $(e.currentTarget);
			const id = $btn.data('id');
			const status = $btn.data('status');

			this.ajax('rt_update_booking_status', { id: id, status: status }, function (response) {
				if (response.success) {
					RTAdmin.showNotice('success', response.data.message);
					RTAdmin.loadBookings(RTAdmin.state.currentPage);
				} else {
					RTAdmin.showNotice('error', response.data.message);
				}
			});
		},

		// =====================================================================
		// Alerts
		// =====================================================================

		/**
		 * Load alerts configuration
		 */
		loadAlerts: function () {
			this.ajax('rt_get_alerts_config', {}, function (response) {
				if (response.success) {
					RTAdmin.populateAlerts(response.data);
				}
			});
		},

		/**
		 * Populate alerts form
		 */
		populateAlerts: function (config) {
			const types = ['confirmation', 'reminder', 'thanks', 'cancellation', 'rescheduled'];

			types.forEach(function (type) {
				if (config[type]) {
					const $item = $(`.rt-alert-item[data-type="${type}"]`);
					const alertConfig = config[type];

					$item.find('.rt-alert-toggle').prop('checked', alertConfig.active);

					// Channels
					if (alertConfig.channels) {
						alertConfig.channels.forEach(function (channel) {
							$item.find(`input[name="alerts[${type}][channels][]"][value="${channel}"]`).prop('checked', true);
						});
					}

					// Subject and message
					$item.find(`input[name="alerts[${type}][subject]"]`).val(alertConfig.subject);
					$item.find(`textarea[name="alerts[${type}][message]"]`).val(alertConfig.message);

					// Offset
					if (alertConfig.offset) {
						$item.find(`input[name="alerts[${type}][offset]"]`).val(alertConfig.offset);
					}
				}
			});
		},

		/**
		 * Toggle alert config visibility
		 */
		toggleAlertConfig: function (e) {
			e.preventDefault();
			const $btn = $(e.currentTarget);
			const $item = $btn.closest('.rt-alert-item');
			const $body = $item.find('.rt-alert-body');

			$body.slideToggle(200);
			$btn.text($body.is(':visible') ? 'Ocultar Configuración' : 'Editar Configuración');
		},

		/**
		 * Toggle alert active state
		 */
		toggleAlertActive: function (e) {
			const $toggle = $(e.target);
			const $item = $toggle.closest('.rt-alert-item');
			const isActive = $toggle.is(':checked');

			$item.toggleClass('alert-active', isActive);
		},

		/**
		 * Save alerts configuration
		 */
		saveAlerts: function (e) {
			e.preventDefault();

			const $btn = $('#rt-save-alerts');
			const $spinner = $btn.siblings('.spinner');

			$btn.prop('disabled', true);
			$spinner.addClass('is-active');

			const data = { alerts: {} };
			const types = ['confirmation', 'reminder', 'thanks', 'cancellation', 'rescheduled'];

			types.forEach(function (type) {
				const $item = $(`.rt-alert-item[data-type="${type}"]`);

				data.alerts[type] = {
					active: $item.find('.rt-alert-toggle').is(':checked') ? 1 : 0,
					channels: [],
					subject: $item.find(`input[name="alerts[${type}][subject]"]`).val(),
					message: $item.find(`textarea[name="alerts[${type}][message]"]`).val(),
				};

				$item.find(`input[name="alerts[${type}][channels][]"]:checked`).each(function () {
					data.alerts[type].channels.push($(this).val());
				});

				// Offset for reminder and thanks
				const $offset = $item.find(`input[name="alerts[${type}][offset]"]`);
				if ($offset.length) {
					data.alerts[type].offset = $offset.val();
				}
			});

			this.ajax('rt_save_alerts_config', data, function (response) {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');

				if (response.success) {
					RTAdmin.showNotice('success', response.data.message);
				} else {
					RTAdmin.showNotice('error', response.data.message);
				}
			});
		},

		/**
		 * Preview alert template
		 */
		previewAlert: function (e) {
			e.preventDefault();
			const $btn = $(e.currentTarget);
			const type = $btn.data('alert');
			const $item = $(`.rt-alert-item[data-type="${type}"]`);

			const data = {
				subject: $item.find(`input[name="alerts[${type}][subject]"]`).val(),
				message: $item.find(`textarea[name="alerts[${type}][message]"]`).val(),
			};

			this.ajax('rt_preview_alert', data, function (response) {
				if (response.success) {
					alert('Asunto: ' + response.data.subject + '\n\n' + response.data.message.replace(/<br>/g, '\n'));
				}
			});
		},

		// =====================================================================
		// Integrations
		// =====================================================================

		/**
		 * Test Twilio connection
		 */
		testTwilio: function (e) {
			e.preventDefault();

			const phone = prompt('Ingresa un número de teléfono para la prueba (con código de país):');

			if (!phone) return;

			const $btn = $(e.currentTarget);
			$btn.prop('disabled', true).text(rtAdmin.strings.saving);

			this.ajax('rt_test_twilio', { phone: phone }, function (response) {
				$btn.prop('disabled', false).text('Enviar Mensaje de Prueba');

				if (response.success) {
					RTAdmin.showNotice('success', response.data.message);
				} else {
					RTAdmin.showNotice('error', response.data.message);
				}
			});
		},

		/**
		 * Save Zoom integration
		 */
		saveZoom: function (e) {
			e.preventDefault();

			const $form = $('#rt-integration-zoom');
			const $btn = $(e.currentTarget);

			$btn.prop('disabled', true).text(rtAdmin.strings.saving);

			const data = {
				type: 'zoom',
				settings: {
					client_id: $form.find('input[name="zoom_api_key"]').val(),
					client_secret: $form.find('input[name="zoom_api_secret"]').val(),
				}
			};

			this.ajax('rt_save_integration', data, function (response) {
				$btn.prop('disabled', false).text('Guardar y Conectar');

				if (response.success) {
					RTAdmin.showNotice('success', response.data.message);
				} else {
					RTAdmin.showNotice('error', response.data.message);
				}
			});
		},

		// =====================================================================
		// Utility Functions
		// =====================================================================

		/**
		 * Format date
		 */
		formatDate: function (dateStr) {
			if (!dateStr) return '';
			const date = new Date(dateStr + 'T00:00:00');
			return date.toLocaleDateString('es-ES', {
				day: '2-digit',
				month: 'short',
				year: 'numeric'
			});
		},

		/**
		 * Format date and time
		 */
		formatDateTime: function (dateTimeStr) {
			if (!dateTimeStr) return '';
			const date = new Date(dateTimeStr);
			return date.toLocaleDateString('es-ES', {
				day: '2-digit',
				month: 'short',
				hour: '2-digit',
				minute: '2-digit'
			});
		},

		/**
		 * Escape HTML
		 */
		escapeHtml: function (text) {
			if (!text) return '';
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	};

	// Initialize on DOM ready
	$(document).ready(function () {
		RTAdmin.init();
	});

})(jQuery);
