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

// La reserva ahora se guarda en el servidor. Aquí solo se evita el doble envío;
// las reglas (horario, disponibilidad, duplicados) se validan en PHP.
document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', () => {
    const boton = form.querySelector('button[type="submit"]');
    if (boton) {
      boton.disabled = true;
      boton.textContent = 'Enviando…';
    }
  });
});
