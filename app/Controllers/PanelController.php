<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\OrdenModel;
use App\Models\ReporteModel;
use App\Models\ReservaModel;

/** Inicio del panel: indicadores y accesos distintos para cada rol (RF-07, RF-09). */
final class PanelController extends Controller
{
    public function index(): never
    {
        $reportes = new ReporteModel();
        $reservas = new ReservaModel();
        $ordenes = new OrdenModel();
        $id = (int) Auth::id();

        match (Auth::rol()) {
            'admin' => $this->vista('panel/admin', [
                'titulo' => 'Resumen del restaurante',
                'kpi' => $reportes->indicadoresAdmin(),
                'serie' => $reportes->ventasPorDia(14),
                'top' => $reportes->platosMasVendidos(date('Y-m-d', strtotime('-30 days')), date('Y-m-d')),
                'hoy' => $reservas->confirmadasDeHoy(),
            ]),
            'mesero' => $this->vista('panel/mesero', [
                'titulo' => 'Mi turno',
                'kpi' => $reportes->indicadoresMesero($id),
                'ordenes' => $ordenes->activasDeMesero($id),
                'hoy' => $reservas->confirmadasDeHoy(),
            ]),
            'cocina' => $this->vista('panel/cocina', [
                'titulo' => 'Cocina hoy',
                'kpi' => $reportes->indicadoresCocina(),
            ]),
            default => $this->vista('panel/cliente', [
                'titulo' => 'Mi cuenta',
                'kpi' => $reportes->indicadoresCliente($id),
                'proximas' => $reservas->proximasDeCliente($id),
                'sinResena' => $ordenes->pagadasSinResena($id),
            ]),
        };
    }
}
