<?php
/**
 * Barras verticales de una sola serie en SVG, generadas en el servidor con datos reales.
 * @var array $serie [['dia','total','cuentas']]
 */
$ancho = 720; $alto = 240; $izq = 56; $der = 8; $arriba = 14; $abajo = 26;
$maximo = max(1, ...array_column($serie, 'total'));
// Escala redonda: 1, 2 o 5 por potencia de 10.
$potencia = 10 ** floor(log10($maximo));
$tope = 10 * $potencia;
foreach ([1, 2, 5, 10] as $m) {
    if ($m * $potencia >= $maximo) { $tope = $m * $potencia; break; }
}
$areaAlto = $alto - $arriba - $abajo;
$areaAncho = $ancho - $izq - $der;
$paso = $areaAncho / count($serie);
$grosor = max(4, $paso - 2); // 2 px de separación entre barras
$indiceMax = array_search($maximo, array_column($serie, 'total'), true);
?>
<div class="grafica-scroll" tabindex="0" role="region" aria-label="Gráfica de ventas diarias">
  <svg class="grafica" viewBox="0 0 <?= $ancho ?> <?= $alto ?>" role="img" aria-label="Ventas diarias de los últimos <?= count($serie) ?> días. El detalle está en la tabla inferior.">
    <?php for ($i = 0; $i <= 2; $i++): $v = $tope * $i / 2; $y = $arriba + $areaAlto - $areaAlto * $i / 2; ?>
      <line class="grafica-rejilla" x1="<?= $izq ?>" x2="<?= $ancho - $der ?>" y1="<?= $y ?>" y2="<?= $y ?>"/>
      <text class="grafica-eje" x="<?= $izq - 8 ?>" y="<?= $y + 4 ?>" text-anchor="end"><?= e($v >= 1000000 ? number_format($v / 1000000, 1, ',', '') . ' M' : number_format($v / 1000, 0, ',', '.') . ' mil') ?></text>
    <?php endfor; ?>
    <?php foreach ($serie as $i => $d):
        $x = $izq + $i * $paso + 1;
        $h = $areaAlto * $d['total'] / $tope;
        $y = $arriba + $areaAlto - $h;
        $r = min(4, $grosor / 2, $h);
        $base = $arriba + $areaAlto;
        $tip = date('d/m', strtotime($d['dia'])) . ' · ' . dinero($d['total']) . ' · ' . $d['cuentas'] . ' cuentas';
    ?>
      <g class="grafica-punto" data-tip="<?= e($tip) ?>">
        <title><?= e($tip) ?></title>
        <rect class="grafica-objetivo" x="<?= $izq + $i * $paso ?>" y="<?= $arriba ?>" width="<?= $paso ?>" height="<?= $areaAlto ?>"/>
        <?php if ($h > 0): ?>
          <path class="grafica-barra" d="M<?= $x ?> <?= $base ?> V<?= $y + $r ?> Q<?= $x ?> <?= $y ?> <?= $x + $r ?> <?= $y ?> H<?= $x + $grosor - $r ?> Q<?= $x + $grosor ?> <?= $y ?> <?= $x + $grosor ?> <?= $y + $r ?> V<?= $base ?> Z"/>
        <?php endif; ?>
        <?php if ($i === $indiceMax && $d['total'] > 0): ?>
          <text class="grafica-valor" x="<?= $x + $grosor / 2 ?>" y="<?= max(10, $y - 4) ?>" text-anchor="middle"><?= e(number_format($d['total'] / 1000, 0, ',', '.')) ?> mil</text>
        <?php endif; ?>
      </g>
      <?php if ($i % 2 === (count($serie) - 1) % 2): ?>
        <text class="grafica-eje" x="<?= $x + $grosor / 2 ?>" y="<?= $alto - 8 ?>" text-anchor="middle"><?= e(date('d/m', strtotime($d['dia']))) ?></text>
      <?php endif; ?>
    <?php endforeach; ?>
  </svg>
</div>
<details class="grafica-tabla">
  <summary>Ver datos en tabla</summary>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th scope="col">Día</th><th scope="col" class="text-end">Ventas</th><th scope="col" class="text-end">Cuentas</th></tr></thead>
      <tbody>
        <?php foreach ($serie as $d): ?>
          <tr><td><?= e(fecha_corta($d['dia'])) ?></td><td class="text-end"><?= e(dinero($d['total'])) ?></td><td class="text-end"><?= e($d['cuentas']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</details>
