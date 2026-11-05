<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ReporteModel;
use App\Models\UsuarioModel;
use App\Services\AuditService;

final class ReporteController extends Controller
{
    public function index(): never
    {
        $f = $this->filtros();
        $modelo = new ReporteModel();
        $this->vista('reportes/index', [
            'titulo' => 'Reporte de ventas',
            'f' => $f,
            'lista' => $modelo->ventas($f, $this->request->page()),
            'resumen' => $modelo->resumenVentas($f),
            'top' => $modelo->platosMasVendidos($f['desde'], $f['hasta'], 5),
            'meseros' => (new UsuarioModel())->opcionesPorRol('mesero'),
        ]);
    }

    /** Exportación CSV con los mismos filtros que se ven en pantalla (RF-09). */
    public function exportar(): never
    {
        $f = $this->filtros();
        $filas = (new ReporteModel())->ventasParaExportar($f);
        AuditService::registrar('reporte_exportado', 'reporte', null, ['desde' => $f['desde'], 'hasta' => $f['hasta'], 'filas' => count($filas)]);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ventas_' . $f['desde'] . '_' . $f['hasta'] . '.csv"');
        $salida = fopen('php://output', 'wb');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel reconoce las tildes.
        fputcsv($salida, ['Fecha', 'Orden', 'Mesa', 'Mesero', 'Método', 'Subtotal', 'Impoconsumo', 'Propina', 'Total'], ';');
        foreach ($filas as $v) {
            fputcsv($salida, array_map([$this, 'celdaSegura'], [
                $v['creado_en'], $v['codigo'], $v['mesa'], $v['mesero'], ReporteModel::METODOS[$v['metodo']] ?? $v['metodo'],
                $v['subtotal'], $v['impoconsumo'], $v['propina'], $v['total'],
            ]), ';');
        }
        fclose($salida);
        exit;
    }

    /** Evita inyección de fórmulas al abrir el CSV en una hoja de cálculo. */
    private function celdaSegura(mixed $valor): string
    {
        $texto = (string) $valor;
        return preg_match('/^[=+\-@\t\r]/', $texto) ? "'" . $texto : $texto;
    }

    private function filtros(): array
    {
        return [
            'desde' => $this->fechaQuery('desde', date('Y-m-d', strtotime('-30 days'))),
            'hasta' => $this->fechaQuery('hasta', date('Y-m-d')),
            'mesero_id' => $this->request->query('mesero_id'),
            'metodo' => $this->request->query('metodo'),
        ];
    }
}
