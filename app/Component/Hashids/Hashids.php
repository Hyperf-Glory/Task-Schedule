<?php
declare(strict_types = 1);

namespace App\Component\Hashids;

use Hashids\Hashids as CoreHashids;
use Psr\Container\ContainerInterface;

/**
 * Class Hashids
 * @package App\Component\Hashids
 * @method encode(...$params)
 * @method decode(string $hash)
 * @method encodeHex(string $str)
 * @method decodeHex(string $hash)
 */
class Hashids
{
    /**
     * @var CoreHashids
     */
    protected $hashids;

    public function __construct(string $salt = 'hashids', int $min = 10)
    {
        $this->hashids = new CoreHashids($salt, $min);
    }

    /**
     * @param $method
     * @param $params
     *
     * @return mixed
     */
    public function __call($method, $params)
    {
        if (!method_exists($this->hashids, $method)) {
            throw new HashidsMethodNotExistsException(sprintf('Hashids method#:%s not exist.', $method));
        }
        return $this->hashids->$method($params);
    }
}
