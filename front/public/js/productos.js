/**
 * JavaScript para el módulo de Productos
 * Maneja la creación, edición, categorías y visualización de productos
 */

let BASE_URL_PRODUCTOS = '';

document.addEventListener('DOMContentLoaded', function() {
    BASE_URL_PRODUCTOS = window.BASE_URL || '';
    
    // Generar código de barras
    function generarCodigo() {
        const prefix = '200';
        const random = Math.floor(Math.random() * 1000000000).toString().padStart(9, '0');
        return prefix + random;
    }
    
    // Generar código al abrir modal nuevo producto
    const modalNuevo = document.getElementById('modalNuevoProducto');
    const codigoInput = document.getElementById('codigo_barras_modalNuevoProducto');
    if (modalNuevo && codigoInput) {
        modalNuevo.addEventListener('show.bs.modal', function() {
            if (!codigoInput.value) {
                codigoInput.value = generarCodigo();
            }
        });
    }
    
    // Botón generar código
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btnGenerarCodigo')) {
            const targetId = e.target.closest('.btnGenerarCodigo').getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input) input.value = generarCodigo();
        }
    });
    
    // Limpiar formulario al cerrar modal
    if (modalNuevo) {
        modalNuevo.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('formNuevoProducto');
            if (form) form.reset();
        });
    }
    
    // Formulario nuevo producto - prevenir doble envío
    const formNuevo = document.getElementById('formNuevoProducto');
    if (formNuevo) {
        let isSubmitting = false; // Bandera para prevenir doble envío
        
        formNuevo.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Prevenir doble envío
            if (isSubmitting) {
                console.warn('Formulario ya se está enviando, ignorando segundo envío');
                return false;
            }
            
            if (!this.checkValidity()) {
                this.reportValidity();
                return false;
            }
            
            // Marcar como enviando
            isSubmitting = true;
            
            // Deshabilitar botón de envío
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
                
                // Re-habilitar después de 5 segundos como fallback (por si hay error de red)
                setTimeout(() => {
                    if (isSubmitting) {
                        isSubmitting = false;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }, 5000);
            }
            
            // Enviar formulario
            this.submit();
            
            return false;
        });
    }

    // Ver imagen de producto en modal genérico
    const modalVerImagenEl = document.getElementById('modalVerImagenProducto');
    let modalVerImagen = null;
    const imgVistaProducto = document.getElementById('imgVistaProducto');

    if (modalVerImagenEl && imgVistaProducto) {
        modalVerImagen = new bootstrap.Modal(modalVerImagenEl);

        document.addEventListener('click', function(e) {
            const thumb = e.target.closest('.img-producto-thumb');
            if (thumb) {
                const src = thumb.getAttribute('data-imagen') || thumb.getAttribute('src');
                if (!src) return;
                imgVistaProducto.src = src;
                modalVerImagen.show();
            }
        });
    }

    function abrirCodigoBarras(origen) {
        const codigo = origen.getAttribute('data-codigo');
        const nombre = origen.getAttribute('data-nombre') || '';
        const src = origen.getAttribute('src');
        const img = document.getElementById('imgVistaCodigo');
        const modalEl = document.getElementById('modalVerCodigoProducto');
        if (!codigo || !src || !img || !modalEl) return;
        img.src = src;
        const nombreEl = document.getElementById('nombreCodigoProducto');
        const textoEl = document.getElementById('textoCodigoProducto');
        const btnDescarga = document.getElementById('btnDescargarCodigo');
        const btnImprimir = document.getElementById('btnImprimirCodigoModal');
        if (nombreEl) nombreEl.textContent = nombre;
        if (textoEl) textoEl.textContent = codigo;
        if (btnDescarga) {
            btnDescarga.href = src;
            btnDescarga.setAttribute('download', 'codigo-' + codigo + '.svg');
        }
        if (btnImprimir) {
            btnImprimir.setAttribute('data-codigo', codigo);
            btnImprimir.setAttribute('data-nombre', nombre);
            btnImprimir.setAttribute('data-src', src);
        }
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function imprimirCodigoBarras(src, nombre) {
        if (!src) return;
        const ventana = window.open('', '_blank', 'width=480,height=360');
        if (!ventana) return;
        const titulo = (nombre || 'Código de barras').replace(/</g, '');
        ventana.document.write(
            '<html><head><title>' + titulo + '</title></head>' +
            '<body style="text-align:center;font-family:sans-serif;">' +
            '<p>' + titulo + '</p>' +
            '<img src="' + src + '" alt="Código de barras" style="width:420px;height:auto;">' +
            '<script>window.onload=function(){window.print();};<\/script>' +
            '</body></html>'
        );
        ventana.document.close();
    }

    document.addEventListener('click', function(e) {
        const caja = e.target.closest('.img-barcode-tabla');
        if (caja) {
            e.preventDefault();
            abrirCodigoBarras(caja);
            return;
        }
        const btnPrint = e.target.closest('#btnImprimirCodigoModal, .btnImprimirCodigo');
        if (btnPrint) {
            imprimirCodigoBarras(btnPrint.getAttribute('data-src'), btnPrint.getAttribute('data-nombre'));
        }
    });
    
    // Editar producto
    document.addEventListener('click', function(e) {
        const btnEditar = e.target.closest('.btnEditarProducto');
        if (btnEditar) {
            e.preventDefault();
            e.stopPropagation();
            
            const productoId = btnEditar.getAttribute('data-id');
            if (!productoId) {
                console.error('No se encontró el ID del producto');
                return;
            }
            
            const modalBody = document.getElementById('modalEditarProductoBody');
            const modalElement = document.getElementById('modalEditarProducto');
            
            if (!modalBody || !modalElement) {
                alert('Error: No se encontró el modal de edición');
                return;
            }
            
            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border"></div><p class="mt-2">Cargando...</p></div>';
            
            let modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
            }
            modalInstance.show();
            
            fetch((BASE_URL_PRODUCTOS || window.BASE_URL || '') + 'index.php?action=productos&method=edit&id=' + productoId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const p = data.producto;
                        const cats = data.categorias;
                        
                        let catOptions = '<option value="">Seleccione...</option>';
                        cats.forEach(cat => {
                            const sel = p.categoria_id == cat.id ? 'selected' : '';
                            catOptions += '<option value="' + cat.id + '" ' + sel + '>' + cat.nombre + ' (' + (cat.total_productos || 0) + ')</option>';
                        });
                        
                        let fotoHtml = '';
                        if (p.foto) {
                            const baseUrl = BASE_URL_PRODUCTOS || window.BASE_URL || '';
                            const rutaFoto = baseUrl + 'front/public/uploads/productos/' + p.foto;
                            fotoHtml = ''
                                + '<div class="mt-2 d-flex align-items-center gap-2">'
                                +   '<img src="' + rutaFoto + '" alt="Foto actual"'
                                +        ' class="img-thumbnail img-producto-thumb"'
                                +        ' onerror="this.onerror=null; this.style.display=\'none\'; this.nextElementSibling && (this.nextElementSibling.style.display=\'block\');">'
                                +   '<span style="display:none; color:#9a918e;"><i class="bi bi-image"></i> Imagen no disponible</span>'
                                +   '<a href="' + rutaFoto + '" target="_blank" style="display:inline;">Ver grande</a>'
                                + '</div>';
                        }

                        let barcodeHtml = '';
                        if (p.codigo_barras) {
                            const barcodeUrl = 'https://barcode.tec-it.com/barcode.ashx?data=' + encodeURIComponent(p.codigo_barras) + '&code=EAN13';
                            const nombreCodigo = String(p.nombre || '').replace(/"/g, '&quot;');
                            barcodeHtml = ''
                                + '<div class="mt-2 text-center">'
                                +   '<img src="' + barcodeUrl + '" alt="Código de barras"'
                                +        ' class="img-fluid img-barcode-tabla"'
                                +        ' data-codigo="' + p.codigo_barras + '"'
                                +        ' data-nombre="' + nombreCodigo + '"'
                                +        ' title="Ver código de barras">'
                                + '</div>';
                        }
                        
                        const baseUrl = BASE_URL_PRODUCTOS || window.BASE_URL || '';
                        modalBody.innerHTML = `
                            <form id="formEditarProducto" method="POST" action="${baseUrl}index.php?action=productos&method=update&id=${p.id}" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="${window.CSRF_TOKEN || ''}">
                                <input type="hidden" name="id" value="${p.id}">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Código de Barras</label>
                                        <input type="text" class="form-control" name="codigo_barras" value="${p.codigo_barras || ''}" readonly>
                                        ${barcodeHtml}
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre *</label>
                                        <input type="text" class="form-control" name="nombre" value="${p.nombre || ''}" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Descripción</label>
                                        <textarea class="form-control" name="descripcion" rows="2">${p.descripcion || ''}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Color *</label>
                                        <input type="text" class="form-control" name="color" value="${p.color || ''}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Talla *</label>
                                        <select class="form-select" name="talla" required>
                                            <option value="">Seleccione...</option>
                                            <option value="0" ${p.talla == '0' ? 'selected' : ''}>0</option>
                                            <option value="2" ${p.talla == '2' ? 'selected' : ''}>2</option>
                                            <option value="4" ${p.talla == '4' ? 'selected' : ''}>4</option>
                                            <option value="6" ${p.talla == '6' ? 'selected' : ''}>6</option>
                                            <option value="8" ${p.talla == '8' ? 'selected' : ''}>8</option>
                                            <option value="10" ${p.talla == '10' ? 'selected' : ''}>10</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Categoría *</label>
                                        <select class="form-select" name="categoria_id" required>${catOptions}</select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Precio Costo *</label>
                                        <div class="input-group"><span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="precio_costo" value="${p.precio_costo || ''}" step="0.01" required></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Precio Venta *</label>
                                        <div class="input-group"><span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="precio_venta" value="${p.precio_venta || ''}" step="0.01" required></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stock *</label>
                                        <input type="number" class="form-control" name="stock" value="${p.stock || 0}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stock Actual</label>
                                        <input type="number" class="form-control" name="stock_minimo" value="${p.stock_minimo || 0}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="estado">
                                            <option value="Disponible" ${p.estado == 'Disponible' ? 'selected' : ''}>Disponible</option>
                                            <option value="Agotado" ${p.estado == 'Agotado' ? 'selected' : ''}>Agotado</option>
                                            <option value="Vendido" ${p.estado == 'Vendido' ? 'selected' : ''}>Vendido</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Foto</label>
                                        <input type="file" class="form-control" name="foto" accept="image/*">
                                        <small class="text-muted">Dejar vacío para mantener la foto actual</small>
                                        ${fotoHtml}
                                    </div>
                                </div>
                            </form>
                        `;
                        
                        let footer = document.getElementById('modalEditarProductoFooter');
                        if (!footer) {
                            footer = document.createElement('div');
                            footer.className = 'modal-footer';
                            footer.id = 'modalEditarProductoFooter';
                            modalElement.querySelector('.modal-content').appendChild(footer);
                        }
                        footer.style.display = 'block';
                        footer.innerHTML = `
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" form="formEditarProducto" class="btn btn-primary">Actualizar</button>
                        `;
                        
                        document.getElementById('formEditarProducto').addEventListener('submit', function(e) {
                            e.preventDefault();
                            if (!this.checkValidity()) {
                                this.reportValidity();
                                return;
                            }
                            this.submit();
                        });
                    } else {
                        alert('Error al cargar producto: ' + (data.error || 'Error desconocido'));
                        modalInstance.hide();
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                });
        }
    });
    
    // Abrir modal nueva categoría desde modal categorías (delegación de eventos)
    document.addEventListener('click', function(e) {
        if (e.target.closest('#btnAbrirNuevaCategoria')) {
            e.preventDefault();
            e.stopPropagation();
            
            const btn = e.target.closest('#btnAbrirNuevaCategoria');
            const modalCategoriasElement = document.getElementById('modalCategorias');
            const modalNuevaCategoriaElement = document.getElementById('modalNuevaCategoria');
            
            if (!modalNuevaCategoriaElement) {
                console.error('Modal nueva categoría no encontrado en el DOM');
                return;
            }
            
            // Remover el foco del botón para evitar la advertencia de accesibilidad
            btn.blur();
            
            const modalCategorias = modalCategoriasElement ? bootstrap.Modal.getInstance(modalCategoriasElement) : null;
            let modalNuevaCategoria = bootstrap.Modal.getInstance(modalNuevaCategoriaElement);
            
            if (!modalNuevaCategoria) {
                modalNuevaCategoria = new bootstrap.Modal(modalNuevaCategoriaElement);
            }
            
            if (modalCategorias) {
                modalCategorias.hide();
                modalCategoriasElement.addEventListener('hidden.bs.modal', function() {
                    modalNuevaCategoria.show();
                }, { once: true });
            } else {
                modalNuevaCategoria.show();
            }
        }
    });
    
    // Crear categoría
    const btnGuardarCategoria = document.getElementById('btnGuardarCategoria');
    if (btnGuardarCategoria) {
        btnGuardarCategoria.addEventListener('click', function() {
            const nombre = document.getElementById('nombre_categoria').value.trim();
            if (!nombre) {
                alert('El nombre es obligatorio');
                return;
            }
            
            const formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('descripcion', document.getElementById('descripcion_categoria').value.trim());
            
            fetch((BASE_URL_PRODUCTOS || window.BASE_URL || '') + 'index.php?action=productos&method=crearCategoria', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modalNuevaCategoriaElement = document.getElementById('modalNuevaCategoria');
                    if (modalNuevaCategoriaElement) {
                        const modalInstance = bootstrap.Modal.getInstance(modalNuevaCategoriaElement);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                    document.getElementById('formNuevaCategoria').reset();
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(() => alert('Error al crear categoría'));
        });
    }
    
    // Eliminar categoría - Optimizado con delegación de eventos más eficiente
    const tablaCategorias = document.getElementById('tablaCategorias');
    if (tablaCategorias) {
        tablaCategorias.addEventListener('click', function(e) {
            const btn = e.target.closest('.btnEliminarCategoria');
            if (!btn) return; // Salir temprano si no es el botón correcto
            
            e.preventDefault();
            e.stopPropagation();
            
            const id = btn.getAttribute('data-id');
            const nombre = btn.getAttribute('data-nombre');
            const productos = parseInt(btn.getAttribute('data-productos') || 0);
            
            // Validar que el ID esté presente y sea válido
            if (!id || id === '' || id === 'null' || id === 'undefined') {
                alert('Error: ID de categoría no válido');
                return;
            }
            
            // Validar que sea un número válido
            const idNum = parseInt(id);
            if (isNaN(idNum) || idNum <= 0) {
                alert('Error: ID de categoría inválido');
                return;
            }
            
            if (productos > 0) {
                alert('No se puede eliminar. Tiene ' + productos + ' productos asociados.');
                return;
            }
            
            if (typeof pedirDobleConfirmacion !== 'function') return;
            pedirDobleConfirmacion({
                titulo: 'Eliminar categoría',
                detalle: 'Se borra la categoría y no se puede recuperar.',
                codigo: nombre,
                alConfirmar: function (escrito) {
            btn.disabled = true;
            const textoOriginal = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            
            const formData = new FormData();
            formData.append('id', idNum);
            formData.append('codigo_confirmacion', escrito);
            
            fetch((BASE_URL_PRODUCTOS || window.BASE_URL || '') + 'index.php?action=productos&method=eliminarCategoria', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remover la fila de la tabla en lugar de recargar toda la página
                    const fila = btn.closest('tr');
                    if (fila) {
                        fila.style.transition = 'opacity 0.3s';
                        fila.style.opacity = '0';
                        setTimeout(() => {
                            fila.remove();
                            // Si no quedan categorías, mostrar mensaje
                            const filasRestantes = tablaCategorias.querySelectorAll('tr');
                            if (filasRestantes.length === 0 || (filasRestantes.length === 1 && filasRestantes[0].querySelector('.categorias-text-muted'))) {
                                tablaCategorias.innerHTML = '<tr><td colspan="4" class="text-center categorias-text-muted">No hay categorías registradas</td></tr>';
                            }
                        }, 300);
                    } else {
                        // Fallback: recargar si no se puede remover la fila
                        location.reload();
                    }
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                    btn.disabled = false;
                    btn.innerHTML = textoOriginal;
                }
            })
            .catch(error => {
                console.error('Error al eliminar categoría:', error);
                alert('Error al eliminar categoría. Por favor, intente nuevamente.');
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
            });
                }
            });
        });
    }
    
    // Filtro en tiempo real por coincidencias en el campo de búsqueda
    const inputBusqueda = document.getElementById('inputBusqueda');
    const tbodyProductos = document.getElementById('tbodyProductos');
    
    if (inputBusqueda && tbodyProductos) {
        // Función para filtrar productos
        function filtrarProductos() {
            const termino = inputBusqueda.value.toLowerCase().trim();
            const todasLasFilas = Array.from(tbodyProductos.querySelectorAll('tr'));
            
            // Remover mensaje anterior si existe
            const mensajeNoResultados = document.getElementById('mensajeNoResultados');
            if (mensajeNoResultados) {
                mensajeNoResultados.remove();
            }
            
            if (termino === '') {
                // Si está vacío, mostrar todas las filas con atributo data-nombre
                todasLasFilas.forEach(fila => {
                    if (fila.hasAttribute('data-nombre')) {
                        fila.style.display = '';
                    }
                });
                return;
            }
            
            let hayCoincidencias = false;
            
            // Filtrar filas por coincidencias
            todasLasFilas.forEach(fila => {
                // Solo procesar filas que tienen el atributo data-nombre (son productos)
                if (!fila.hasAttribute('data-nombre')) {
                    return;
                }
                
                const nombre = (fila.getAttribute('data-nombre') || '').toLowerCase();
                const color = (fila.getAttribute('data-color') || '').toLowerCase();
                const talla = (fila.getAttribute('data-talla') || '').toLowerCase();
                const categoria = (fila.getAttribute('data-categoria') || '').toLowerCase();
                // Código de barras: buscar coincidencia exacta o parcial, sin espacios
                const codigo = (fila.getAttribute('data-codigo') || '').toLowerCase().replace(/\s/g, '');
                const terminoSinEspacios = termino.replace(/\s/g, '');
                
                const coincide = nombre.includes(termino) || 
                                color.includes(termino) || 
                                talla.includes(termino) || 
                                categoria.includes(termino) || 
                                codigo.includes(terminoSinEspacios);
                
                if (coincide) {
                    hayCoincidencias = true;
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
            
            // Mostrar mensaje si no hay resultados
            if (!hayCoincidencias) {
                const filasConProductos = todasLasFilas.filter(f => f.hasAttribute('data-nombre'));
                if (filasConProductos.length > 0) {
                    const tr = document.createElement('tr');
                    tr.id = 'mensajeNoResultados';
                    tr.innerHTML = '<td colspan="11" class="text-center text-muted">No se encontraron productos que coincidan con la búsqueda</td>';
                    tbodyProductos.appendChild(tr);
                }
            }
        }
        
        // Evento input para filtrar en tiempo real
        inputBusqueda.addEventListener('input', filtrarProductos);
        
        // Evento keyup para limpiar con Escape
        inputBusqueda.addEventListener('keyup', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                filtrarProductos();
            }
        });
        
        // Filtrar al cargar la página si hay un valor en el campo
        if (inputBusqueda.value.trim() !== '') {
            filtrarProductos();
        }
    }
    
    // Limpiar campos de búsqueda después de mostrar resultados (solo si se usó el botón buscar)
    const formFiltros = document.getElementById('formFiltrosProductos');
    if (formFiltros) {
        // Verificar si hay parámetros de búsqueda en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const tieneBusqueda = urlParams.has('busqueda') || urlParams.has('categoria_id') || urlParams.has('estado');
        
        if (tieneBusqueda) {
            // Limpiar campos después de que la página se haya cargado completamente
            setTimeout(function() {
                const selectCategoria = document.getElementById('selectCategoria');
                const selectEstado = document.getElementById('selectEstado');
                
                // No limpiar el input de búsqueda para mantener el filtro en tiempo real
                if (selectCategoria) selectCategoria.value = '';
                if (selectEstado) selectEstado.value = '';
            }, 100);
        }
    }
});

