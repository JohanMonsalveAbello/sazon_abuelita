<?php
// Menú lateral del panel. Cada rol solo ve las opciones para las que tiene permiso (RF-07).
return [
    ['Inicio',      'bi-house-door',    '/panel',      null],
    ['Reservas',    'bi-calendar-check','/reservas',   'reservas.ver'],
    ['Órdenes',     'bi-receipt',       '/ordenes',    'ordenes.ver'],
    ['Cocina',      'bi-fire',          '/cocina',     'cocina.ver'],
    ['Platos',      'bi-egg-fried',     '/platos',     'platos.ver'],
    ['Categorías',  'bi-tags',          '/categorias', 'categorias.ver'],
    ['Mesas',       'bi-grid-3x3-gap',  '/mesas',      'mesas.ver'],
    ['Reseñas',     'bi-star',          '/resenas',    'resenas.ver'],
    ['Reportes',    'bi-bar-chart',     '/reportes',   'reportes.ver'],
    ['Usuarios',    'bi-people',        '/usuarios',   'usuarios.ver'],
    ['Permisos',    'bi-shield-check',  '/permisos',   'permisos.ver'],
    ['Auditoría',   'bi-clock-history', '/auditoria',  'auditoria.ver'],
];
