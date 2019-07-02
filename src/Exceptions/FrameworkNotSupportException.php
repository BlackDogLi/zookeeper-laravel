<?php
/**
 * User: ly
 * Date: 2019/6/24
 * Time: 16:34
 */
namespace Ly\Zookeeper\Exceptions;

use Throwable;

class FrameworkNotSupportException extends \RuntimeException
{
    /**
     * FrameworkNotSupportException constructor.
     * @param string $framework
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct (string $framework, int $code = 0, Throwable $previous = null)
    {
        parent::__construct("Not support framework '{$framework}'", $code, $previous);
    }

}