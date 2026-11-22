// login.js
// Revisa el formulario ANTES de mandarlo, para avisar al toque
// si falta un dato. (Igual el servidor vuelve a validar todo:
// JavaScript se puede desactivar, así que no alcanza solo con esto.)

document.querySelector('form').addEventListener('submit', function (evento) {
    var identificador = document.getElementById('identificador');
    var password = document.getElementById('password');
    var hayError = false;

    [identificador, password].forEach(function (campo) {
        if (campo.value.trim() === '') {
            campo.classList.add('campo-error');
            hayError = true;
        } else {
            campo.classList.remove('campo-error');
        }
    });

    if (hayError) {
        evento.preventDefault(); // no manda el formulario
    }
});
