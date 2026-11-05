<?php
declare(strict_types=1);

namespace App\Core;

/** Base de todos los controladores: renderizado, validación, redirección y mensajes. */
abstract class Controller
{
    public function __construct(protected Request $request)
    {
    }

    protected function vista(string $vista, array $datos = [], string $layout = 'panel'): never
    {
        Response::html(View::render($vista, $datos, $layout));
    }

    protected function redirigir(string $ruta, ?string $exito = null): never
    {
        if ($exito !== null) {
            Session::flash('success', $exito);
        }
        Response::redirect($ruta);
    }

    protected function redirigirConError(string $ruta, string $error): never
    {
        Session::flash('error', $error);
        Response::redirect($ruta);
    }

    /**
     * Validación autoritativa en servidor. Si falla, vuelve a $destino con errores y valores.
     * Las contraseñas nunca se conservan entre solicitudes.
     */
    protected function validar(array $reglas, string $destino): array
    {
        $datos = $this->request->all();
        $errores = Validator::check($datos, $reglas);
        if ($errores !== []) {
            $viejos = array_filter(
                $datos,
                static fn (string $k) => !str_contains($k, 'password') && $k !== '_csrf',
                ARRAY_FILTER_USE_KEY
            );
            throw new ValidationException($errores, $viejos, $destino);
        }
        return array_intersect_key($datos, $reglas) + array_fill_keys(array_keys($reglas), '');
    }

    /** Ejecuta una acción de negocio: éxito o regla incumplida terminan en una redirección clara. */
    protected function ejecutar(callable $accion, string $mensajeOk, string $destinoOk, ?string $destinoError = null): never
    {
        try {
            $accion();
        } catch (BusinessRuleException $e) {
            Session::flash('old', array_filter($this->request->all(), static fn (string $k) => !str_contains($k, 'password') && $k !== '_csrf', ARRAY_FILTER_USE_KEY));
            $this->redirigirConError($destinoError ?? $destinoOk, $e->getMessage());
        } catch (\PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            // Una restricción de la base detuvo la operación (dato duplicado o en uso).
            $this->redirigirConError($destinoError ?? $destinoOk, 'La operación entra en conflicto con otro registro. Actualiza la página y revisa los datos.');
        }
        $this->redirigir($destinoOk, $mensajeOk);
    }

    /** Fecha de un filtro en la URL; una fecha malformada simplemente se ignora. */
    protected function fechaQuery(string $clave, string $porDefecto = ''): string
    {
        $valor = $this->request->query($clave);
        return $valor !== '' && Validator::check([$clave => $valor], [$clave => ['Fecha', 'date']]) === [] ? $valor : $porDefecto;
    }

    protected function denegar(): never
    {
        throw new HttpException(403);
    }

    protected function noEncontrado(): never
    {
        throw new HttpException(404);
    }
}
