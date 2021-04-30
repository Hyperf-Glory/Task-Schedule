<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Component\Serializer;

use Hyperf\Contract\NormalizerInterface;
use SuperClosure\Serializer as SuperClosureSerializer;

class ClosureSerializer implements NormalizerInterface
{
    protected $executor;

    public function __construct()
    {
        $this->executor = new SuperClosureSerializer();
    }

    /**
     * @param mixed $object
     */
    public function normalize($object): ?string
    {
        if (! is_callable($object)) {
            throw new \InvalidArgumentException('Argument invalid, it must be callable.');
        }

        return $this->executor->serialize($object);
    }

    /**
     * @param mixed $data
     *
     * @return \Closure|mixed|object
     */
    public function denormalize($data, string $class = '')
    {
        return $this->executor->unserialize($data);
    }
}
