<?php

namespace ArsamMe\Wallet\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseData implements Arrayable, JsonSerializable {
    public static function fromArray(array $data): static {
        $reflector = new \ReflectionClass(static::class);
        $params = [];

        foreach ($reflector->getConstructor()->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (array_key_exists($name, $data)) {
                $value = $data[$name];

                // Cast to appropriate type if type is declared
                if ($type && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    settype($value, $typeName); // for classes, this won't work, needs custom logic
                } elseif ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                    if ('int' === $typeName) {
                        $value = (int) $value;
                    } elseif ('float' === $typeName) {
                        $value = (float) $value;
                    } elseif ('bool' === $typeName) {
                        $value = (bool) $value;
                    } elseif ('string' === $typeName) {
                        $value = (string) $value;
                    }
                }

                $params[] = $value;
            } elseif ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                // If required param is missing, you may throw or pass null
                $params[] = null;
            }
        }

        return $reflector->newInstanceArgs($params);
    }

    public function toArray(): array {
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        $array = [];
        foreach ($props as $prop) {
            $name = $prop->getName();
            $array[$name] = $this->{$name};
        }

        return $array;
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }

    public function toJson(): string {
        return json_encode($this->toArray());
    }

    public static function fromJson(string $json): self {
        return self::fromArray(json_decode($json, true));
    }
}
