const toggle = document.querySelector('.menu-toggle');
const navbar = document.querySelector('.navbar');
if (toggle) {
  toggle.addEventListener('click', () => {
    const open = navbar.classList.toggle('mobile-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
}

document.querySelectorAll('.nav-links a').forEach(link => {
  link.addEventListener('click', () => navbar.classList.remove('mobile-open'));
});

const filters = document.querySelectorAll('.filter');
const dishes = document.querySelectorAll('.dish-card');
filters.forEach(button => {
  button.addEventListener('click', () => {
    filters.forEach(b => b.classList.remove('active'));
    button.classList.add('active');
    const filter = button.dataset.filter;
    dishes.forEach(card => {
      card.classList.toggle('hidden', filter !== 'all' && card.dataset.category !== filter);
    });
  });
});

const form = document.getElementById('reservationForm');
if (form) {
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const data = new FormData(form);
    const text = [
      'Hola, quiero hacer una reserva en El Sazón de la Abuela.',
      `Nombre: ${data.get('nombre')}`,
      `Teléfono: ${data.get('telefono')}`,
      `Correo: ${data.get('correo')}`,
      `Fecha: ${data.get('fecha')}`,
      `Hora: ${data.get('hora')}`,
      `Personas: ${data.get('personas')}`,
      `Mensaje: ${data.get('mensaje') || 'Sin observaciones'}`
    ].join('\n');
    window.open(`https://wa.me/573001234567?text=${encodeURIComponent(text)}`, '_blank');
  });
}

document.getElementById('year').textContent = new Date().getFullYear();
