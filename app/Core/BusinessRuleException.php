<?php
declare(strict_types=1);

namespace App\Core;

/** Una regla de negocio impide la operación. El mensaje siempre es apto para el usuario. */
final class BusinessRuleException extends \RuntimeException
{
}
