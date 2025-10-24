import DataTable from 'datatables.net-bs5'
import 'datatables.net-responsive-bs5'
import Swal from 'sweetalert2'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'
import { Toast } from '../app'

L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2xUrl,
  iconUrl: markerIconUrl,
  shadowUrl: markerShadowUrl
})

const getLeaflet = () => L

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const orderStatusLabels = {
  confirmed: 'Confirmado',
  shipped: 'Despachado',
  delivered: 'Entregado'
}

const paymentLabels = {
  paid: 'Pagado',
  pending: 'Pendiente',
  partial: 'Parcial'
}

const routeStatusLabels = {
  planned: 'Planificada',
  in_progress: 'En ruta',
  completed: 'Completada'
}

const routeStatusBadges = {
  planned: 'text-bg-warning',
  in_progress: 'text-bg-primary',
  completed: 'text-bg-success'
}

const formatDecimal = (value) => {
  const numeric = Number(value)
  if (!Number.isFinite(numeric)) return '0.0000'
  return numeric.toFixed(4)
}

const formatDateTime = (value) => {
  if (!value) return 'Sin definir'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('es-ES', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

const pluralize = (count, singular, plural) => {
  return count === 1 ? `${count} ${singular}` : `${count} ${plural}`
}

const escapeHtml = (unsafe = '') => {
  return unsafe
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

document.addEventListener('DOMContentLoaded', () => {
  const tableElement = document.querySelector('#deliveriesTable')
  if (!tableElement) return

  const warehouseSelect = document.querySelector('[data-delivery-warehouse]')
  const dateInput = document.querySelector('[data-delivery-date]')
  const notesInput = document.querySelector('[data-delivery-notes]')
  const createRouteButton = document.querySelector('[data-create-route]')
  const selectedCountElement = document.querySelector('[data-selected-count]')
  const routeListContainer = document.querySelector('[data-route-list]')
  const routesCountBadge = document.querySelector('[data-routes-count]')
  const mapTitle = document.querySelector('[data-map-title]')
  const mapSubtitle = document.querySelector('[data-map-subtitle]')
  const mapStatus = document.querySelector('[data-map-status]')
  const mapEmpty = document.querySelector('[data-map-empty]')
  const routeStopsContainer = document.querySelector('[data-route-stops]')

  const mapEmptyDefaultHtml = mapEmpty?.innerHTML || ''

  const selectedOrderIds = new Set()
  let routes = []
  let warehouses = []
  let activeRouteId = null
  let mapInstance = null
  let markersLayer = null
  let polylineLayer = null

  if (createRouteButton) {
    createRouteButton.disabled = true
  }

  const datatable = new DataTable(tableElement, {
    data: [],
    responsive: true,
    paging: true,
    searching: true,
    info: true,
    order: [[1, 'desc']],
    columns: [
      {
        title: '<i class="bi bi-check-square"></i>',
        data: 'id',
        orderable: false,
        searchable: false,
        className: 'text-center align-middle',
        width: '45px',
        render: (data, type, row) => {
          if (type !== 'display') return data
          return `<input type="checkbox" class="form-check-input" data-select-order value="${row.id}">`
        }
      },
      { title: 'Código', data: 'code', defaultContent: '-' },
      { title: 'Cliente', data: 'customer.name', defaultContent: '-' },
      {
        title: 'Total',
        data: 'grand_total',
        className: 'text-end',
        render: (value) => formatDecimal(value)
      },
      {
        title: 'Estado',
        data: 'status',
        className: 'text-center',
        render: (value) => {
          const label = orderStatusLabels[value] ?? value ?? '-'
          return `<span class="badge text-bg-light">${label}</span>`
        }
      },
      {
        title: 'Pago',
        data: 'payment_status',
        className: 'text-center',
        render: (value) => {
          const label = paymentLabels[value] ?? value ?? '-'
          const badgeClass = value === 'paid' ? 'text-bg-success' : 'text-bg-secondary'
          return `<span class="badge ${badgeClass}">${label}</span>`
        }
      }
    ],
    rowCallback: (row, data) => {
      const checkbox = row.querySelector('[data-select-order]')
      if (checkbox) {
        const isSelected = selectedOrderIds.has(String(data.id))
        checkbox.checked = isSelected
        row.classList.toggle('table-primary', isSelected)
      }
    }
  })

  const updateSelectedCount = () => {
    const count = selectedOrderIds.size
    if (selectedCountElement) {
      selectedCountElement.textContent = count
        ? pluralize(count, 'pedido seleccionado', 'pedidos seleccionados')
        : '0 pedidos seleccionados'
    }
    if (createRouteButton) {
      createRouteButton.disabled = count === 0
    }
  }

  const renderWarehouses = (list = []) => {
    warehouses = list
    if (!warehouseSelect) return
    const previous = warehouseSelect.value
    const options = ['<option value="">Seleccionar automáticamente</option>']

    list.forEach((warehouse) => {
      const selected = previous && String(warehouse.id) === previous ? 'selected' : ''
      const label = warehouse.label || `${warehouse.code ? `${warehouse.code} - ` : ''}${warehouse.name}`
      options.push(`<option value="${warehouse.id}" ${selected}>${label}</option>`)
    })

    warehouseSelect.innerHTML = options.join('')
    if (previous && !list.some((warehouse) => String(warehouse.id) === previous)) {
      warehouseSelect.value = ''
    }
  }

  const ensureMap = () => {
    const Leaflet = getLeaflet()

    if (!Leaflet) {
      showMapEmpty('No fue posible cargar el mapa de Leaflet. Verifica tu conexión a internet.')
      return null
    }

    if (mapInstance) {
      return mapInstance
    }

    mapInstance = Leaflet.map('deliveryRouteMap', {
      center: [4.624335, -74.063644],
      zoom: 6,
      scrollWheelZoom: false
    })

    Leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>'
    }).addTo(mapInstance)

    markersLayer = Leaflet.layerGroup().addTo(mapInstance)

    return mapInstance
  }

  const resetMapLayers = () => {
    if (markersLayer) {
      markersLayer.clearLayers()
    }
    if (polylineLayer && mapInstance) {
      mapInstance.removeLayer(polylineLayer)
      polylineLayer = null
    }
  }

  const showMapEmpty = (message = null) => {
    if (!mapEmpty) return
    mapEmpty.innerHTML = message
      ? `<i class="bi bi-geo-alt text-muted d-block fs-3 mb-2"></i><p class="text-muted mb-0">${escapeHtml(message)}</p>`
      : mapEmptyDefaultHtml
    mapEmpty.classList.remove('d-none')
  }

  const hideMapEmpty = () => {
    if (!mapEmpty) return
    mapEmpty.classList.add('d-none')
  }

  const renderRouteStops = (route) => {
    if (!routeStopsContainer) return

    if (!route) {
      routeStopsContainer.innerHTML = '<p class="text-muted small mb-0">Selecciona una ruta para ver sus paradas.</p>'
      return
    }

    const stops = []

    if (route.warehouse) {
      stops.push({
        label: `Salida: ${route.warehouse.name || 'Almacén'}`,
        subtitleHtml: escapeHtml(route.warehouse.code ? `Código ${route.warehouse.code}` : 'Sin código'),
        delivered: true
      })
    }

    route.orders.forEach((order, index) => {
      const customerName = order.customer?.name
        ? `Cliente: ${order.customer.name}`
        : 'Cliente sin nombre'
      const address = order.customer?.address
        ? `Dirección: ${order.customer.address}`
        : 'Dirección no registrada'
      const deliveredAt = order.delivered_at ? `Entregado: ${formatDateTime(order.delivered_at)}` : 'Pendiente de entrega'

      stops.push({
        label: `${index + 1}. Pedido ${order.code}`,
        subtitleHtml: `${escapeHtml(customerName)}<br>${escapeHtml(address)}`,
        delivered: Boolean(order.is_delivered),
        deliveredAt
      })
    })

    if (!stops.length) {
      routeStopsContainer.innerHTML = '<p class="text-muted small mb-0">Esta ruta aún no tiene paradas registradas.</p>'
      return
    }

    const html = [
      '<h6 class="text-uppercase small fw-semibold text-muted mb-2">Paradas de la ruta</h6>',
      '<ol class="delivery-stops-list list-unstyled mb-0">'
    ]

    stops.forEach((stop, index) => {
      html.push(`
        <li class="delivery-stop-item ${stop.delivered ? 'is-completed' : ''}">
          <div class="delivery-stop-index">${index + 1}</div>
          <div class="delivery-stop-content">
            <p class="delivery-stop-title mb-1">${escapeHtml(stop.label)}</p>
            <p class="delivery-stop-subtitle text-muted small mb-1">${stop.subtitleHtml ?? ''}</p>
            <span class="badge ${stop.delivered ? 'text-bg-success' : 'text-bg-warning'}">${stop.delivered ? 'Entregado' : 'Pendiente'}</span>
            <span class="delivery-stop-meta small text-muted ms-2">${escapeHtml(stop.deliveredAt || '')}</span>
          </div>
        </li>
      `)
    })

    html.push('</ol>')
    routeStopsContainer.innerHTML = html.join('')
  }

  const renderRouteOnMap = () => {
    const route = routes.find((item) => String(item.id) === String(activeRouteId))

    if (!route) {
      resetMapLayers()
      if (mapTitle) mapTitle.textContent = 'Mapa de la ruta'
      if (mapSubtitle) mapSubtitle.textContent = 'Selecciona una ruta para visualizarla y ver sus paradas.'
      if (mapStatus) {
        mapStatus.className = 'badge text-bg-light'
        mapStatus.textContent = 'Sin selección'
      }
      showMapEmpty()
      renderRouteStops(null)
      return
    }

    const map = ensureMap()
    const Leaflet = getLeaflet()

    if (!map || !Leaflet) {
      renderRouteStops(route)
      return
    }

    resetMapLayers()

    if (mapTitle) mapTitle.textContent = `Mapa de la ruta ${route.code}`
    if (mapSubtitle) mapSubtitle.textContent = route.scheduled_at
      ? `Programada para ${formatDateTime(route.scheduled_at)}`
      : 'Sin fecha programada'
    if (mapStatus) {
      const badgeClass = routeStatusBadges[route.status] ?? 'text-bg-secondary'
      mapStatus.className = `badge ${badgeClass}`
      mapStatus.textContent = routeStatusLabels[route.status] ?? route.status ?? 'Sin estado'
    }

    const points = []

    if (route.warehouse && route.warehouse.latitude !== null && route.warehouse.longitude !== null) {
      points.push({
        coords: [route.warehouse.latitude, route.warehouse.longitude],
        label: route.warehouse.name ? `Almacén ${route.warehouse.name}` : 'Almacén de salida'
      })
    }

    route.orders.forEach((order, index) => {
      if (order.customer?.latitude !== null && order.customer?.longitude !== null) {
        points.push({
          coords: [order.customer.latitude, order.customer.longitude],
          label: `Pedido ${order.code}${order.customer?.name ? ` - ${order.customer.name}` : ''}`,
          delivered: Boolean(order.is_delivered),
          position: index + 1
        })
      }
    })

    if (!points.length) {
      showMapEmpty('Agrega coordenadas al almacén y a los clientes para visualizar la ruta en el mapa.')
      renderRouteStops(route)
      setTimeout(() => map.invalidateSize(), 150)
      return
    }

    hideMapEmpty()

    const markerIconFactory = (leaflet, point, index) => {
      if (!leaflet) return null

      if (index === 0 && route.warehouse) {
        return leaflet.divIcon({
          className: 'delivery-marker delivery-marker-warehouse',
          html: '<span class="delivery-marker-label"><i class="bi bi-geo-alt"></i></span>',
          iconSize: [36, 36],
          iconAnchor: [18, 18]
        })
      }

      return leaflet.divIcon({
        className: `delivery-marker delivery-marker-stop ${point.delivered ? 'is-delivered' : ''}`,
        html: `<span class="delivery-marker-label">${point.position ?? index}</span>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16]
      })
    }

    const coordinates = []

    points.forEach((point, index) => {
      coordinates.push(point.coords)
      const icon = markerIconFactory(Leaflet, point, index)
      const marker = Leaflet.marker(point.coords, { icon })
      marker.bindPopup(point.label)
      markersLayer.addLayer(marker)
    })

    if (coordinates.length > 1) {
      polylineLayer = Leaflet.polyline(coordinates, {
        color: '#2563eb',
        weight: 4,
        opacity: 0.75
      }).addTo(map)
      map.fitBounds(polylineLayer.getBounds(), { padding: [24, 24] })
    } else {
      map.setView(coordinates[0], 14)
    }

    setTimeout(() => map.invalidateSize(), 150)
    renderRouteStops(route)
  }

  const renderRoutes = () => {
    if (routesCountBadge) {
      routesCountBadge.textContent = routes.length
    }

    if (!routeListContainer) return

    if (!routes.length) {
      routeListContainer.innerHTML = '<div class="text-muted small">No hay rutas planificadas aún. Selecciona pedidos y genera la primera ruta.</div>'
      activeRouteId = null
      renderRouteOnMap()
      return
    }

    if (!activeRouteId || !routes.some((route) => String(route.id) === String(activeRouteId))) {
      activeRouteId = routes[0]?.id ?? null
    }

    const cards = routes.map((route) => {
      const isActive = String(route.id) === String(activeRouteId)
      const deliveredCount = route.orders.filter((order) => order.is_delivered).length
      const ordersHtml = route.orders
        .map((order, index) => {
          const customerName = order.customer?.name ? ` - ${order.customer.name}` : ''
          const deliveredBadge = order.is_delivered
            ? '<span class="badge text-bg-success">Entregado</span>'
            : ''

          const action = order.is_delivered
            ? deliveredBadge
            : `<button type="button" class="btn btn-sm btn-outline-primary" data-deliver-order data-route-id="${route.id}" data-order-id="${order.id}" data-order-code="${escapeHtml(order.code)}">
                <i class="bi bi-truck me-1"></i> Entregar
              </button>`

          return `
            <li class="delivery-route-order">
              <div class="delivery-route-order-info">
                <span class="delivery-route-order-index">${index + 1}</span>
                <div>
                  <p class="delivery-route-order-title mb-0">Pedido ${escapeHtml(order.code)}${escapeHtml(customerName)}</p>
                  <p class="delivery-route-order-meta text-muted small mb-0">${order.delivered_at ? `Entregado: ${formatDateTime(order.delivered_at)}` : 'Pendiente de entrega'}</p>
                </div>
              </div>
              <div class="delivery-route-order-actions">
                ${action}
              </div>
            </li>
          `
        })
        .join('')

      const badgeClass = routeStatusBadges[route.status] ?? 'text-bg-secondary'
      const warehouseLabel = route.warehouse
        ? `${route.warehouse.code ? `${route.warehouse.code} - ` : ''}${route.warehouse.name}`
        : 'Sin almacén asignado'

      return `
        <div class="delivery-route-card ${isActive ? 'is-active' : ''}" data-route-card data-route-id="${route.id}">
          <div class="delivery-route-card-header">
            <div>
              <p class="delivery-route-code mb-0">${escapeHtml(route.code)}</p>
              <span class="delivery-route-scheduled text-muted small">${route.scheduled_at ? `Programada: ${formatDateTime(route.scheduled_at)}` : 'Sin fecha programada'}</span>
            </div>
            <div class="text-end">
              <span class="badge ${badgeClass}">${routeStatusLabels[route.status] ?? route.status ?? ''}</span>
              <p class="text-muted small mb-0">${deliveredCount}/${route.orders.length} entregas</p>
            </div>
          </div>
          <div class="delivery-route-card-body">
            <p class="delivery-route-warehouse text-muted small mb-3"><i class="bi bi-box-seam me-1"></i>${escapeHtml(warehouseLabel)}</p>
            <ul class="delivery-route-orders list-unstyled mb-0">
              ${ordersHtml || '<li class="text-muted small">No hay pedidos asignados.</li>'}
            </ul>
          </div>
        </div>
      `
    })

    routeListContainer.innerHTML = cards.join('')
    renderRouteOnMap()
  }

  const sanitizeSelection = (orders) => {
    const availableIds = new Set(orders.map((order) => String(order.id)))
    Array.from(selectedOrderIds).forEach((id) => {
      if (!availableIds.has(id)) {
        selectedOrderIds.delete(id)
      }
    })
    updateSelectedCount()
  }

  const loadDeliveries = async (preferredRouteId = null) => {
    try {
      const response = await fetch('/deliveries', {
        headers: { Accept: 'application/json' },
        credentials: 'include'
      })

      if (!response.ok) {
        Toast.fire({ icon: 'error', title: 'No se pudieron cargar los pedidos para entrega' })
        return
      }

      const payload = await response.json()
      const orders = Array.isArray(payload.orders) ? payload.orders : []
      const warehousesPayload = Array.isArray(payload.warehouses) ? payload.warehouses : []
      routes = Array.isArray(payload.routes) ? payload.routes : []

      datatable.clear()
      datatable.rows.add(orders)
      datatable.draw()

      sanitizeSelection(orders)
      renderWarehouses(warehousesPayload)

      if (preferredRouteId) {
        activeRouteId = preferredRouteId
      }

      renderRoutes()
      updateSelectedCount()
    } catch (error) {
      console.error('Error cargando entregas', error)
    }
  }

  const createRoute = async () => {
    if (!selectedOrderIds.size) {
      Toast.fire({ icon: 'info', title: 'Selecciona al menos un pedido' })
      return
    }

    const orderCount = selectedOrderIds.size
    const result = await Swal.fire({
      icon: 'question',
      title: 'Generar ruta de entrega',
      html: `<p class="mb-1">Se crear\u00e1 una ruta con ${pluralize(orderCount, 'pedido', 'pedidos')}.</p><p class="text-muted mb-0">La ruta quedar\u00e1 disponible para marcar entregas y visualizarse en el mapa.</p>`,
      showCancelButton: true,
      confirmButtonText: 'Crear ruta',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#2563eb'
    })

    if (!result.isConfirmed) {
      return
    }

    const originalHtml = createRouteButton?.innerHTML
    if (createRouteButton) {
      createRouteButton.disabled = true
      createRouteButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Generando...'
    }

    const formData = new FormData()
    selectedOrderIds.forEach((id) => formData.append('order_ids[]', id))
    if (warehouseSelect?.value) {
      formData.append('warehouse_id', warehouseSelect.value)
    }
    if (dateInput?.value) {
      formData.append('scheduled_at', dateInput.value)
    }
    if (notesInput?.value) {
      formData.append('notes', notesInput.value)
    }

    try {
      const response = await fetch('/delivery-routes', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          Accept: 'application/json'
        },
        body: formData,
        credentials: 'include'
      })

      const payload = await response.json().catch(() => ({}))

      if (response.status === 422) {
        const message = Object.values(payload.errors || {})
          .flat()
          .find(Boolean)
        Toast.fire({ icon: 'error', title: message || 'No se pudo crear la ruta' })
        return
      }

      if (!response.ok) {
        Toast.fire({ icon: 'error', title: 'No se pudo crear la ruta' })
        return
      }

      const routeId = payload?.route?.id
      Toast.fire({ icon: 'success', title: 'Ruta creada correctamente' })
      selectedOrderIds.clear()
      notesInput && (notesInput.value = '')
      await loadDeliveries(routeId)
      document.dispatchEvent(new Event('orders:request-refresh'))
    } catch (error) {
      console.error('Error creando la ruta', error)
      Toast.fire({ icon: 'error', title: 'Ocurrió un error al crear la ruta' })
    } finally {
      if (createRouteButton) {
        createRouteButton.disabled = selectedOrderIds.size === 0
        createRouteButton.innerHTML = originalHtml || 'Generar ruta'
      }
      updateSelectedCount()
    }
  }

  const deliverOrder = async (orderId, routeId, orderCode, button) => {
    if (!orderId || !routeId) return

    const result = await Swal.fire({
      icon: 'question',
      title: 'Confirmar entrega',
      text: `¿Deseas marcar el pedido ${orderCode} como entregado?`,
      showCancelButton: true,
      confirmButtonText: 'Sí, entregar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#198754'
    })

    if (!result.isConfirmed) {
      return
    }

    const originalHtml = button?.innerHTML
    if (button) {
      button.disabled = true
      button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Entregando...'
    }

    const formData = new FormData()
    formData.append('delivery_route_id', routeId)

    if (warehouseSelect?.value) {
      formData.append('warehouse_id', warehouseSelect.value)
    }
    if (dateInput?.value) {
      formData.append('moved_at', dateInput.value)
    }

    try {
      const response = await fetch(`/orders/${orderId}/deliver`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          Accept: 'application/json'
        },
        body: formData,
        credentials: 'include'
      })

      const payload = await response.json().catch(() => ({}))

      if (response.status === 422) {
        const message = Object.values(payload.errors || {})
          .flat()
          .find(Boolean)
        Toast.fire({ icon: 'error', title: message || 'No se pudo entregar el pedido' })
        return
      }

      if (!response.ok) {
        Toast.fire({ icon: 'error', title: 'No se pudo entregar el pedido' })
        return
      }

      Toast.fire({ icon: 'success', title: 'Pedido entregado correctamente' })
      await loadDeliveries(routeId)
      document.dispatchEvent(new Event('orders:request-refresh'))
    } catch (error) {
      console.error('Error entregando pedido', error)
      Toast.fire({ icon: 'error', title: 'Ocurrió un error al entregar el pedido' })
    } finally {
      if (button) {
        button.disabled = false
        button.innerHTML = originalHtml || 'Entregar'
      }
    }
  }

  tableElement.addEventListener('change', (event) => {
    const checkbox = event.target.closest('[data-select-order]')
    if (!checkbox) return

    const orderId = checkbox.value
    if (!orderId) return

    if (checkbox.checked) {
      selectedOrderIds.add(orderId)
    } else {
      selectedOrderIds.delete(orderId)
    }

    updateSelectedCount()
  })

  routeListContainer?.addEventListener('click', (event) => {
    const deliverButton = event.target.closest('[data-deliver-order]')
    if (deliverButton) {
      const orderId = deliverButton.getAttribute('data-order-id')
      const routeId = deliverButton.getAttribute('data-route-id')
      const orderCode = deliverButton.getAttribute('data-order-code') || ''
      deliverOrder(orderId, routeId, orderCode, deliverButton)
      event.stopPropagation()
      return
    }

    const card = event.target.closest('[data-route-card]')
    if (!card) return

    const routeId = card.getAttribute('data-route-id')
    if (!routeId || String(routeId) === String(activeRouteId)) return

    activeRouteId = routeId
    renderRoutes()
  })

  createRouteButton?.addEventListener('click', createRoute)

  loadDeliveries()

  document.addEventListener('deliveries:request-refresh', () => {
    loadDeliveries(activeRouteId)
  })
})
