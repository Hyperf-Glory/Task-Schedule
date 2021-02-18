<?php
declare(strict_types = 1);

namespace App\Component\Serializer;

use Hyperf\Contract\NormalizerInterface;

class ObjectSerializer implements NormalizerInterface
{
    public function normalize($object)
    {
        return serialize($object);
    }

    public function denormalize($data, string $class = '') : object
    {
        $str    = pack('N', strlen($data)) . $data . "\r\n";
        $strlen = strlen($data);
        return swoole_substr_unserialize($str, 4, $strlen);
    }
}
