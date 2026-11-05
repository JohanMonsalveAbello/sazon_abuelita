// JavaScript del panel. Mejora la experiencia, pero nunca reemplaza las validaciones del servidor.
(function () {
  'use strict';

  // Validación amigable en el navegador: usa las reglas HTML5 de cada campo y estilos de Bootstrap.
  document.querySelectorAll('form.js-validar').forEach(function (form) {
    form.addEventListener('submit', function (evento) {
      form.querySelectorAll('.form-control, .form-select').forEach(function (campo) {
        var feedback = campo.parentElement.querySelector('.invalid-feedback');
        campo.classList.toggle('is-invalid', !campo.validity.valid);
        campo.setAttribute('aria-invalid', String(!campo.validity.valid));
        if (feedback && !campo.validity.valid) {
          feedback.textContent = campo.validationMessage;
        }
      });
      if (!form.checkValidity()) {
        evento.preventDefault();
        evento.stopImmediatePropagation();
        var primero = form.querySelector(':invalid');
        if (primero) { primero.focus(); }
      }
    });
    form.addEventListener('input', function (evento) {
      if (evento.target.validity && evento.target.validity.valid) {
        evento.target.classList.remove('is-invalid');
        evento.target.removeAttribute('aria-invalid');
      }
    });
  });

  // Confirmación de acciones destructivas con el modal de Bootstrap.
  var modalEl = document.getElementById('modalConfirmar');
  var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
  var formPendiente = null;
  document.addEventListener('submit', function (evento) {
    var form = evento.target;
    if (!modal || !form.dataset.confirmar || form.dataset.confirmado === '1') { return; }
    evento.preventDefault();
    formPendiente = form;
    document.getElementById('modalConfirmarTexto').textContent = form.dataset.confirmar;
    modal.show();
  });
  if (modalEl) {
    document.getElementById('modalConfirmarSi').addEventListener('click', function () {
      if (formPendiente) {
        formPendiente.dataset.confirmado = '1';
        modal.hide();
        formPendiente.requestSubmit();
      }
    });
  }

  // Evita doble envío de formularios POST.
  document.addEventListener('submit', function (evento) {
    var form = evento.target;
    if (evento.defaultPrevented || form.method !== 'post') { return; }
    var boton = form.querySelector('button[type="submit"]');
    if (boton) { setTimeout(function () { boton.disabled = true; }, 0); }
  });

  // Tablero de cocina: actualización periódica sin recargar la página.
  var tablero = document.getElementById('tablero');
  if (tablero) {
    var actualizar = function () {
      if (document.hidden) { return; }
      fetch(tablero.dataset.url, { credentials: 'same-origin' })
        .then(function (respuesta) {
          // Si la sesión expiró, el servidor redirige al login: se recarga para mostrarlo.
          if (respuesta.redirected || !respuesta.ok) { window.location.reload(); return null; }
          return respuesta.text();
        })
        .then(function (html) {
          // Solo se reemplaza el tablero si quien cocina no está a punto de pulsar un botón.
          if (html !== null && !tablero.contains(document.activeElement)) { tablero.innerHTML = html; }
        })
        .catch(function () { /* Sin conexión: se reintenta en el siguiente ciclo. */ });
    };
    setInterval(actualizar, 15000);
  }

  // Información emergente de las gráficas.
  var tip = document.getElementById('tip');
  if (tip) {
    document.addEventListener('mousemove', function (evento) {
      var objetivo = evento.target.closest ? evento.target.closest('[data-tip]') : null;
      if (!objetivo) { tip.hidden = true; return; }
      tip.textContent = objetivo.dataset.tip;
      tip.hidden = false;
      var x = Math.min(evento.clientX + 12, window.innerWidth - tip.offsetWidth - 8);
      tip.style.left = x + 'px';
      tip.style.top = (evento.clientY - tip.offsetHeight - 10) + 'px';
    });
  }
})();
