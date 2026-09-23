document.addEventListener('change', function (e) {
    if (!e.target.matches('.permisos-switch input')) {
        return;
    }
    const texto = e.target.parentElement.querySelector('.permisos-switch-texto');
    if (texto) {
        texto.textContent = e.target.checked ? 'Permitido' : 'Bloqueado';
    }
    const conteo = document.getElementById('conteoRol');
    if (conteo) {
        conteo.textContent = String(document.querySelectorAll('.permisos-editor input[name="slugs[]"]:checked').length);
    }
});
