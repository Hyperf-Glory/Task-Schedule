<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Component\Serializer;

use Hyperf\Contract\NormalizerInterface;
use Hyperf\Utils\Exception\InvalidArgumentException;

class JsonSerializer implements NormalizerInterface
{
    public function normalize($object)
    {
        try {
            return json_encode($object, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param mixed $data
     *
     * @return array|mixed
     */
    public function denormalize($data, string $class = null): array
    {
        $data = sprintf('%s%s%s', pack('N', strlen($data)), $data, "\r\n");
        $strlen = strlen($data);
        return swoole_substr_json_decode($data, 4, $strlen - 6, true);
    }
}
