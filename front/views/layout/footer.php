    <div class="modal fade" id="modalDobleEliminar" tabindex="-1" aria-labelledby="modalDobleEliminarTitulo" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDobleEliminarTitulo">Eliminar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p id="modalDobleEliminarDetalle"></p>
                    <p class="mb-2">Escribe <strong id="modalDobleEliminarCodigoTexto"></strong> para confirmar.</p>
                    <label for="modalDobleEliminarCampo" class="form-label">Confirmación</label>
                    <input type="text" class="form-control" id="modalDobleEliminarCampo" autocomplete="off">
                    <p class="text-danger small mt-2 d-none" id="modalDobleEliminarAviso">No coincide.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="modalDobleEliminarOk" disabled>Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalAviso" tabindex="-1" aria-labelledby="modalAvisoTitulo" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAvisoTitulo">Aviso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p id="modalAvisoTexto" class="mb-0" style="white-space: pre-line;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary d-none" id="modalAvisoCancelar" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="modalAvisoOk">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>front/public/js/main.js?v=3"></script>
    <script src="<?php echo BASE_URL; ?>front/public/js/permisos.js?v=3"></script>
</body>
</html>

