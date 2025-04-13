<?php

namespace ArsamMe\Wallet\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseData implements Arrayable, JsonSerializable
{
    public static function fromArray(array $data): static
    {
        return new static($data);
    }

    public function toArray(): array
    {
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        $array = [];
        foreach ($props as $prop) {
            $name = $prop->getName();
            $array[$name] = $this->{$name};
        }

        return $array;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true));
    }

    // Optional: allow $data->something access if needed
    public function __get(string $name): mixed
    {
        return $this->{$name} ?? null;
    }
}
