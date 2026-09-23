/**
 * JavaScript para el módulo de Gastos
 * Maneja la creación, edición de gastos, inversiones, categorías e historial
 */

let BASE_URL_GASTOS = '';

// Función para esperar a que Bootstrap esté disponible
function waitForBootstrap(callback) {
    if (typeof bootstrap !== 'undefined') {
        callback();
    } else {
        // Intentar cada 100ms hasta que Bootstrap esté disponible
        setTimeout(function() {
            waitForBootstrap(callback);
        }, 100);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    BASE_URL_GASTOS = window.BASE_URL || '';
    
    // Esperar a que Bootstrap esté disponible antes de continuar
    waitForBootstrap(function() {
        inicializarGastos();
    });
});

function inicializarGastos() {
    // Verificar que Bootstrap esté cargado
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no está cargado. Los modales no funcionarán.');
        return;
    }
    
    const form = document.getElementById('formNuevoGasto');
    const btnGuardar = document.getElementById('btnGuardarGasto');
    const btnGuardarTexto = document.getElementById('btnGuardarGastoTexto');
    const modalNuevoGastoEl = document.getElementById('modalNuevoGasto');
    const modalTitle = document.getElementById('modalNuevoGastoLabel');
    let esEdicion = false;
    
    // Verificar que los elementos existan
    if (!modalNuevoGastoEl) {
        console.warn('Modal modalNuevoGasto no encontrado');
    }
    
    // Función auxiliar para obtener o crear instancia del modal
    function getModalInstance() {
        if (!modalNuevoGastoEl) return null;
        return bootstrap.Modal.getInstance(modalNuevoGastoEl) || new bootstrap.Modal(modalNuevoGastoEl);
    }
    
    // Función para editar gasto
    window.editarGasto = function(gastoId) {
        esEdicion = true;
        if (modalTitle) modalTitle.innerHTML = '<i class="bi bi-pencil"></i> Editar Gasto';
        if (btnGuardarTexto) btnGuardarTexto.textContent = 'Actualizar Gasto';
        
        // Cargar datos del gasto
        fetch(`${BASE_URL_GASTOS || window.BASE_URL || ''}index.php?action=gastos&method=getGasto&id=${gastoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.gasto) {
                    const g = data.gasto;
                    const gastoIdInput = document.getElementById('gasto_id');
                    if (gastoIdInput) gastoIdInput.value = g.id;
                    const conceptoInput = document.getElementById('concepto');
                    if (conceptoInput) conceptoInput.value = g.concepto || '';
                    const montoInput = document.getElementById('monto');
                    if (montoInput) montoInput.value = g.monto || '';
                    const categoriaInput = document.getElementById('categoria');
                    if (categoriaInput) categoriaInput.value = g.categoria || 'Otros';
                    const fechaInput = document.getElementById('fecha');
                    if (fechaInput) fechaInput.value = g.fecha || '';
                    const descripcionInput = document.getElementById('descripcion');
                    if (descripcionInput) descripcionInput.value = g.descripcion || '';
                    
                    // Mostrar el modal
                    const modal = getModalInstance();
                    if (modal) modal.show();
                } else {
                    alert('Error al cargar los datos del gasto');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los datos del gasto');
            });
    };
    
    if (btnGuardar) {
        btnGuardar.addEventListener('click', function() {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const gastoId = document.getElementById('gasto_id').value;
            const formData = new FormData();
            
            if (esEdicion && gastoId) {
                // Actualizar gasto existente
                formData.append('id', gastoId);
                formData.append('concepto', document.getElementById('concepto').value);
                formData.append('monto', document.getElementById('monto').value);
                formData.append('categoria', document.getElementById('categoria').value);
                formData.append('fecha', document.getElementById('fecha').value);
                formData.append('descripcion', document.getElementById('descripcion').value);
                
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Actualizando...';
                
                fetch((BASE_URL_GASTOS || window.BASE_URL || '') + 'index.php?action=gastos&method=update', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .then(data => {
                    if (data) {
                        try {
                            const json = JSON.parse(data);
                            if (json.success) {
                                const modal = getModalInstance();
                                if (modal) modal.hide();
                                window.location.reload();
                            } else {
                                alert('Error: ' + (json.error || 'No se pudo actualizar el gasto'));
                                btnGuardar.disabled = false;
                                btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarGastoTexto">Actualizar Gasto</span>';
                            }
                        } catch (e) {
                            window.location.reload();
                        }
                    } else {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar el gasto. Por favor, intente nuevamente.');
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarGastoTexto">Actualizar Gasto</span>';
                });
            } else {
                // Crear nuevo gasto
                formData.append('concepto', document.getElementById('concepto').value);
                formData.append('monto', document.getElementById('monto').value);
                formData.append('categoria', document.getElementById('categoria').value);
                formData.append('fecha', document.getElementById('fecha').value);
                formData.append('descripcion', document.getElementById('descripcion').value);
                
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Guardando...';
                
                fetch((BASE_URL_GASTOS || window.BASE_URL || '') + 'index.php?action=gastos&method=store', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .then(data => {
                    if (data) {
                        try {
                            const json = JSON.parse(data);
                            if (json.success) {
                                const modal = getModalInstance();
                                if (modal) modal.hide();
                                window.location.reload();
                            } else {
                                alert('Error: ' + (json.error || 'No se pudo crear el gasto'));
                                btnGuardar.disabled = false;
                                btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarGastoTexto">Guardar Gasto</span>';
                            }
                        } catch (e) {
                            window.location.reload();
                        }
                    } else {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al crear el gasto. Por favor, intente nuevamente.');
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarGastoTexto">Guardar Gasto</span>';
                });
            }
        });
    }
    
    // Limpiar formulario al cerrar el modal
    const modalNuevoGasto = document.getElementById('modalNuevoGasto');
    if (modalNuevoGasto) {
        modalNuevoGasto.addEventListener('hidden.bs.modal', function() {
            if (form) form.reset();
            const gastoIdInput = document.getElementById('gasto_id');
            if (gastoIdInput) gastoIdInput.value = '';
            const fechaInput = document.getElementById('fecha');
            if (fechaInput) fechaInput.value = '';
            esEdicion = false;
            if (modalTitle) modalTitle.innerHTML = '<i class="bi bi-plus-circle"></i> Nuevo Gasto';
            if (btnGuardarTexto) btnGuardarTexto.textContent = 'Guardar Gasto';
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarGastoTexto">Guardar Gasto</span>';
            }
        });
    }
    
    // Función para escapar HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // ========== GESTIÓN DE INVERSIONES ==========
    const formInversion = document.getElementById('formNuevaInversion');
    const btnGuardarInversion = document.getElementById('btnGuardarInversion');
    const btnGuardarInversionTexto = document.getElementById('btnGuardarInversionTexto');
    const modalInversionEl = document.getElementById('modalNuevaInversion');
    const modalInversionTitle = document.getElementById('modalNuevaInversionLabel');
    let esEdicionInversion = false;
    
    if (modalInversionEl) {
        const modalInversion = new bootstrap.Modal(modalInversionEl);
        
        // Función para editar inversión
        window.editarInversion = function(inversionId) {
            esEdicionInversion = true;
            if (modalInversionTitle) modalInversionTitle.innerHTML = '<i class="bi bi-pencil"></i> Editar Inversión';
            if (btnGuardarInversionTexto) btnGuardarInversionTexto.textContent = 'Actualizar Inversión';
            
            fetch(`${BASE_URL_GASTOS || window.BASE_URL || ''}index.php?action=gastos&method=getInversion&id=${inversionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.inversion) {
                        const inv = data.inversion;
                        const inversionIdInput = document.getElementById('inversion_id');
                        if (inversionIdInput) inversionIdInput.value = inv.id;
                        const conceptoInput = document.getElementById('inversion_concepto');
                        if (conceptoInput) conceptoInput.value = inv.concepto || '';
                        const montoInput = document.getElementById('inversion_monto');
                        if (montoInput) montoInput.value = inv.monto || '';
                        const categoriaInput = document.getElementById('inversion_categoria');
                        if (categoriaInput) categoriaInput.value = inv.categoria || 'Otros';
                        const fechaInput = document.getElementById('inversion_fecha');
                        if (fechaInput) fechaInput.value = inv.fecha || '';
                        const descripcionInput = document.getElementById('inversion_descripcion');
                        if (descripcionInput) descripcionInput.value = inv.descripcion || '';
                        modalInversion.show();
                    } else {
                        alert('Error al cargar los datos de la inversión');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos de la inversión');
                });
        };
        
        if (btnGuardarInversion) {
            btnGuardarInversion.addEventListener('click', function() {
                if (!formInversion || !formInversion.checkValidity()) {
                    if (formInversion) formInversion.reportValidity();
                    return;
                }
                
                const inversionId = document.getElementById('inversion_id')?.value;
                const formData = new FormData();
                
                if (esEdicionInversion && inversionId) {
                    formData.append('id', inversionId);
                    formData.append('concepto', document.getElementById('inversion_concepto').value);
                    formData.append('monto', document.getElementById('inversion_monto').value);
                    formData.append('categoria', document.getElementById('inversion_categoria').value);
                    formData.append('fecha', document.getElementById('inversion_fecha').value);
                    formData.append('descripcion', document.getElementById('inversion_descripcion').value);
                    
                    btnGuardarInversion.disabled = true;
                    btnGuardarInversion.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Actualizando...';
                    
                    fetch((BASE_URL_GASTOS || window.BASE_URL || '') + 'index.php?action=gastos&method=updateInversion', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.text();
                        }
                    })
                    .then(data => {
                        if (data) {
                            try {
                                const json = JSON.parse(data);
                                if (json.success) {
                                    modalInversion.hide();
                                    window.location.reload();
                                } else {
                                    alert('Error: ' + (json.error || 'No se pudo actualizar la inversión'));
                                    btnGuardarInversion.disabled = false;
                                    btnGuardarInversion.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarInversionTexto">Actualizar Inversión</span>';
                                }
                            } catch (e) {
                                window.location.reload();
                            }
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al actualizar la inversión');
                        btnGuardarInversion.disabled = false;
                        btnGuardarInversion.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarInversionTexto">Actualizar Inversión</span>';
                    });
                } else {
                    formData.append('concepto', document.getElementById('inversion_concepto').value);
                    formData.append('monto', document.getElementById('inversion_monto').value);
                    formData.append('categoria', document.getElementById('inversion_categoria').value);
                    formData.append('fecha', document.getElementById('inversion_fecha').value);
                    formData.append('descripcion', document.getElementById('inversion_descripcion').value);
                    
                    btnGuardarInversion.disabled = true;
                    btnGuardarInversion.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
                    
                    fetch((BASE_URL_GASTOS || window.BASE_URL || '') + 'index.php?action=gastos&method=storeInversion', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.text();
                        }
                    })
                    .then(data => {
                        if (data) {
                            try {
                                const json = JSON.parse(data);
                                if (json.success) {
                                    modalInversion.hide();
                                    window.location.reload();
                                } else {
                                    alert('Error: ' + (json.error || 'No se pudo crear la inversión'));
                                    btnGuardarInversion.disabled = false;
                                    btnGuardarInversion.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarInversionTexto">Guardar Inversión</span>';
                                }
                            } catch (e) {
                                window.location.reload();
                            }
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al crear la inversión');
                        btnGuardarInversion.disabled = false;
                        btnGuardarInversion.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarInversionTexto">Guardar Inversión</span>';
                    });
                }
            });
        }
        
        // Limpiar formulario al cerrar el modal
        modalInversionEl.addEventListener('hidden.bs.modal', function() {
            if (formInversion) formInversion.reset();
            const inversionIdInput = document.getElementById('inversion_id');
            if (inversionIdInput) inversionIdInput.value = '';
            const fechaInput = document.getElementById('inversion_fecha');
            if (fechaInput) fechaInput.value = '';
            esEdicionInversion = false;
            if (modalInversionTitle) modalInversionTitle.innerHTML = '<i class="bi bi-graph-up"></i> Nueva Inversión';
            if (btnGuardarInversionTexto) btnGuardarInversionTexto.textContent = 'Guardar Inversión';
            if (btnGuardarInversion) {
                btnGuardarInversion.disabled = false;
                btnGuardarInversion.innerHTML = '<i class="bi bi-save"></i> <span id="btnGuardarInversionTexto">Guardar Inversión</span>';
            }
        });
    }
    
    // ========== GESTIÓN DE HISTORIAL ==========
    const modalHistorialEl = document.getElementById('modalHistorial');
    if (modalHistorialEl) {
        // Cargar historial al abrir el modal
        modalHistorialEl.addEventListener('show.bs.modal', function() {
            cargarHistorial();
        });
    }
    
    // Función para convertir mes a español
    function mesEnEspanol(mesAnio) {
        if (!mesAnio) return '';
        
        const meses = {
            'January': 'Enero', 'February': 'Febrero', 'March': 'Marzo', 'April': 'Abril',
            'May': 'Mayo', 'June': 'Junio', 'July': 'Julio', 'August': 'Agosto',
            'September': 'Septiembre', 'October': 'Octubre', 'November': 'Noviembre', 'December': 'Diciembre'
        };
        
        // Si viene en formato "Month Year" (ej: "January 2024")
        if (mesAnio.includes(' ')) {
            const partes = mesAnio.split(' ');
            const mes = partes[0];
            const anio = partes[1];
            return meses[mes] ? `${meses[mes]} ${anio}` : mesAnio;
        }
        
        // Si viene en formato "YYYY-MM" (ej: "2024-01")
        if (mesAnio.match(/^\d{4}-\d{2}$/)) {
            const [anio, mes] = mesAnio.split('-');
            const mesesNum = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            const mesIndex = parseInt(mes) - 1;
            return `${mesesNum[mesIndex]} ${anio}`;
        }
        
        return mesAnio;
    }
    
    function cargarHistorial() {
        fetch((BASE_URL_GASTOS || window.BASE_URL || '') + 'index.php?action=gastos&method=obtenerHistorial')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Gastos por mes
                    let htmlGastosMes = '';
                    if (data.gastos_por_mes && data.gastos_por_mes.length > 0) {
                        data.gastos_por_mes.forEach(item => {
                            const mesNombre = mesEnEspanol(item.mes_nombre || item.mes);
                            htmlGastosMes += `
                                <tr>
                                    <td>${escapeHtml(mesNombre)}</td>
                                    <td class="text-end"><strong>$${parseFloat(item.total || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                                    <td class="text-center"><span class="badge bg-secondary">${item.cantidad || 0}</span></td>
                                </tr>
                            `;
                        });
                    } else {
                        htmlGastosMes = '<tr><td colspan="3" class="text-center text-muted">No hay datos</td></tr>';
                    }
                    const tablaGastosMes = document.getElementById('tablaGastosMes');
                    if (tablaGastosMes) tablaGastosMes.innerHTML = htmlGastosMes;
                    
                    // Inversiones por mes
                    let htmlInversionesMes = '';
                    if (data.inversiones_por_mes && data.inversiones_por_mes.length > 0) {
                        data.inversiones_por_mes.forEach(item => {
                            const mesNombre = mesEnEspanol(item.mes_nombre || item.mes);
                            htmlInversionesMes += `
                                <tr>
                                    <td>${escapeHtml(mesNombre)}</td>
                                    <td class="text-end"><strong>$${parseFloat(item.total || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                                    <td class="text-center"><span class="badge bg-secondary">${item.cantidad || 0}</span></td>
                                </tr>
                            `;
                        });
                    } else {
                        htmlInversionesMes = '<tr><td colspan="3" class="text-center text-muted">No hay datos</td></tr>';
                    }
                    const tablaInversionesMes = document.getElementById('tablaInversionesMes');
                    if (tablaInversionesMes) tablaInversionesMes.innerHTML = htmlInversionesMes;
                    
                    // Gastos por año
                    let htmlGastosAnio = '';
                    if (data.gastos_por_anio && data.gastos_por_anio.length > 0) {
                        data.gastos_por_anio.forEach(item => {
                            htmlGastosAnio += `
                                <tr>
                                    <td><strong>${item.anio || ''}</strong></td>
                                    <td class="text-end"><strong>$${parseFloat(item.total || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                                    <td class="text-center"><span class="badge bg-secondary">${item.cantidad || 0}</span></td>
                                </tr>
                            `;
                        });
                    } else {
                        htmlGastosAnio = '<tr><td colspan="3" class="text-center text-muted">No hay datos</td></tr>';
                    }
                    const tablaGastosAnio = document.getElementById('tablaGastosAnio');
                    if (tablaGastosAnio) tablaGastosAnio.innerHTML = htmlGastosAnio;
                    
                    // Inversiones por año
                    let htmlInversionesAnio = '';
                    if (data.inversiones_por_anio && data.inversiones_por_anio.length > 0) {
                        data.inversiones_por_anio.forEach(item => {
                            htmlInversionesAnio += `
                                <tr>
                                    <td><strong>${item.anio || ''}</strong></td>
                                    <td class="text-end"><strong>$${parseFloat(item.total || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                                    <td class="text-center"><span class="badge bg-secondary">${item.cantidad || 0}</span></td>
                                </tr>
                            `;
                        });
                    } else {
                        htmlInversionesAnio = '<tr><td colspan="3" class="text-center text-muted">No hay datos</td></tr>';
                    }
                    const tablaInversionesAnio = document.getElementById('tablaInversionesAnio');
                    if (tablaInversionesAnio) tablaInversionesAnio.innerHTML = htmlInversionesAnio;
                } else {
                    console.error('Error al cargar historial');
                }
            })
            .catch(error => {
                console.error('Error al cargar historial:', error);
                const tablaGastosMes = document.getElementById('tablaGastosMes');
                const tablaInversionesMes = document.getElementById('tablaInversionesMes');
                const tablaGastosAnio = document.getElementById('tablaGastosAnio');
                const tablaInversionesAnio = document.getElementById('tablaInversionesAnio');
                if (tablaGastosMes) tablaGastosMes.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error al cargar datos</td></tr>';
                if (tablaInversionesMes) tablaInversionesMes.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error al cargar datos</td></tr>';
                if (tablaGastosAnio) tablaGastosAnio.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error al cargar datos</td></tr>';
                if (tablaInversionesAnio) tablaInversionesAnio.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error al cargar datos</td></tr>';
            });
    }
    
    // ========== GESTIÓN DE CATEGORÍAS DE GASTOS ==========
    const modalCategoriasEl = document.getElementById('modalCategoriasGastos');
    const formCategoria = document.getElementById('formCategoriaGasto');
    const btnNuevaCategoria = document.getElementById('btnNuevaCategoriaGasto');
    const btnGuardarCategoria = document.getElementById('btnGuardarCategoriaGasto');
    const btnCancelarCategoria = document.getElementById('btnCancelarCategoriaGasto');
    const tablaCategorias = document.getElementById('tablaCategoriasGastos');
    let esEdicionCategoria = false;
    
    if (modalCategoriasEl) {
        // Cargar categorías al abrir el modal
        modalCategoriasEl.addEventListener('show.bs.modal', function() {
            cargarCategorias();
        });
    }
    
    function cargarCategorias() {
        fetch((BASE_URL_GASTOS || window.BASE_URL || '') + 'index.php?action=gastos&method=obtenerCategorias')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.categorias) {
                    let html = '';
                    if (data.categorias.length > 0) {
                        data.categorias.forEach(cat => {
                            html += `
                                <tr>
                                    <td><strong>${escapeHtml(cat.nombre || '')}</strong></td>
                                    <td>${escapeHtml(cat.descripcion || '')}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editarCategoriaGasto(${cat.id})" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="eliminarCategoriaGasto(${cat.id})" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        html = '<tr><td colspan="3" class="text-center text-muted">No hay categorías registradas</td></tr>';
                    }
                    if (tablaCategorias) tablaCategorias.innerHTML = html;
                    // Actualizar selects de categorías
                    actualizarSelectsCategorias(data.categorias);
                }
            })
            .catch(error => {
                console.error('Error al cargar categorías:', error);
                if (tablaCategorias) tablaCategorias.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error al cargar categorías</td></tr>';
            });
    }
    
    function actualizarSelectsCategorias(categorias) {
        const selects = document.querySelectorAll('#categoria, select[name="categoria"]');
        selects.forEach(select => {
            const valorActual = select.value;
            select.innerHTML = '<option value="">Seleccione una categoría</option>';
            categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.nombre;
                option.textContent = cat.nombre;
                if (cat.nombre === valorActual) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        });
    }
    
    if (btnNuevaCategoria) {
        btnNuevaCategoria.addEventListener('click', function() {
            esEdicionCategoria = false;
            const categoriaIdInput = document.getElementById('categoria_gasto_id');
            const categoriaNombreInput = document.getElementById('categoria_gasto_nombre');
            const categoriaDescInput = document.getElementById('categoria_gasto_descripcion');
            if (categoriaIdInput) categoriaIdInput.value = '';
            if (categoriaNombreInput) categoriaNombreInput.value = '';
            if (categoriaDescInput) categoriaDescInput.value = '';
            if (formCategoria) formCategoria.style.display = 'block';
            if (categoriaNombreInput) categoriaNombreInput.focus();
        });
    }
    
    if (btnCancelarCategoria) {
        btnCancelarCategoria.addEventListener('click', function() {
            if (formCategoria) formCategoria.style.display = 'none';
            const formNuevaCategoriaGasto = document.getElementById('formNuevaCategoriaGasto');
            if (formNuevaCategoriaGasto) formNuevaCategoriaGasto.reset();
            esEdicionCategoria = false;
        });
    }
    
    window.editarCategoriaGasto = function(id) {
        fetch(`${BASE_URL_GASTOS || window.BASE_URL || ''}index.php?action=gastos&method=getCategoria&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.categoria) {
                    esEdicionCategoria = true;
                    const categoriaIdInput = document.getElementById('categoria_gasto_id');
                    const categoriaNombreInput = document.getElementById('categoria_gasto_nombre');
                    const categoriaDescInput = document.getElementById('categoria_gasto_descripcion');
                    if (categoriaIdInput) categoriaIdInput.value = data.categoria.id;
                    if (categoriaNombreInput) categoriaNombreInput.value = data.categoria.nombre || '';
                    if (categoriaDescInput) categoriaDescInput.value = data.categoria.descripcion || '';
                    if (formCategoria) formCategoria.style.display = 'block';
                    if (categoriaNombreInput) categoriaNombreInput.focus();
                } else {
                    alert('Error al cargar la categoría');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar la categoría');
            });
    };
    
    window.eliminarCategoriaGasto = function(id) {
        if (!confirm('¿Está seguro de eliminar esta categoría?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', id);
        
        fetch((BASE_URL_GASTOS || window.BASE_URL || '') + 'index.php?action=gastos&method=eliminarCategoria', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cargarCategorias();
                alert(data.message || 'Categoría eliminada exitosamente');
            } else {
                alert('Error: ' + (data.error || 'No se pudo eliminar la categoría'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la categoría');
        });
    };
    
    if (btnGuardarCategoria) {
        btnGuardarCategoria.addEventListener('click', function() {
            const form = document.getElementById('formNuevaCategoriaGasto');
            if (!form || !form.checkValidity()) {
                if (form) form.reportValidity();
                return;
            }
            
            const formData = new FormData();
            const categoriaId = document.getElementById('categoria_gasto_id')?.value;
            
            if (esEdicionCategoria && categoriaId) {
                formData.append('id', categoriaId);
                formData.append('nombre', document.getElementById('categoria_gasto_nombre').value);
                formData.append('descripcion', document.getElementById('categoria_gasto_descripcion').value);
                
                btnGuardarCategoria.disabled = true;
                btnGuardarCategoria.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Actualizando...';
                
                fetch((BASE_URL_GASTOS || window.BASE_URL || '') + 'index.php?action=gastos&method=actualizarCategoria', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (formCategoria) formCategoria.style.display = 'none';
                        if (form) form.reset();
                        cargarCategorias();
                        alert(data.message || 'Categoría actualizada exitosamente');
                    } else {
                        alert('Error: ' + (data.error || 'No se pudo actualizar la categoría'));
                    }
                    btnGuardarCategoria.disabled = false;
                    btnGuardarCategoria.innerHTML = '<i class="bi bi-save"></i> Guardar';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar la categoría');
                    btnGuardarCategoria.disabled = false;
                    btnGuardarCategoria.innerHTML = '<i class="bi bi-save"></i> Guardar';
                });
            } else {
                formData.append('nombre', document.getElementById('categoria_gasto_nombre').value);
                formData.append('descripcion', document.getElementById('categoria_gasto_descripcion').value);
                
                btnGuardarCategoria.disabled = true;
                btnGuardarCategoria.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
                
                fetch((BASE_URL_GASTOS || window.BASE_URL || '') + 'index.php?action=gastos&method=crearCategoria', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (formCategoria) formCategoria.style.display = 'none';
                        if (form) form.reset();
                        cargarCategorias();
                        alert(data.message || 'Categoría creada exitosamente');
                    } else {
                        alert('Error: ' + (data.error || 'No se pudo crear la categoría'));
                    }
                    btnGuardarCategoria.disabled = false;
                    btnGuardarCategoria.innerHTML = '<i class="bi bi-save"></i> Guardar';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al crear la categoría');
                    btnGuardarCategoria.disabled = false;
                    btnGuardarCategoria.innerHTML = '<i class="bi bi-save"></i> Guardar';
                });
            }
        });
    }
}

