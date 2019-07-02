<?php
/**
 * Created by PhpStorm.
 * User: ly
 * Date: 2019/6/24
 * Time: 16:48
 */

namespace Ly\Zookeeper\Exceptions;


use Throwable;

class ErrorInfoException extends \RuntimeException
{
    public function __construct (string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}