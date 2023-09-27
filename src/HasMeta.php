<?php

namespace Penobit\SuperModels;

trait HasMeta {
    /**
     * get resource metadata records.
     */
    public function metadata(): MorphMany {
        return $this->morphMany(
            config('supermodels.models.meta'),
            'model'
        );
    }

    /**
     * set resource metadata.
     *
     * @param array<string, mixed>|string|Traversable $meta metadata name or array of metadata
     * @param mixed $value metadata value
     */
    public function setMeta(mixed $meta, mixed $value = null): void {
        if (is_array($meta) || is_object($meta) || $meta instanceof \Traversable) {
            $meta = collect($meta);
            $upsert = [];

            foreach ($meta as $k => $v) {
                $upsert[] = [
                    'name' => $k,
                    'value' => $v,
                    'model_type' => $this::class,
                    'model_id' => $this->id,
                ];
            }

            $this->removeMeta($meta->keys());

            $upsert = collect($upsert)->filter(fn ($x) => null !== $x['value'])->toArray();

            $this->metadata()->upsert($upsert, ['name'], ['value']);

            return;
        }

        $this->metadata()->updateOrCreate(['name' => $meta], ['value' => $value]);
    }

    /**
     * get record's metadata by name.
     *
     * @param ?string $meta
     *
     * @return null|array<string, mixed>|string
     */
    public function getMeta(mixed $meta = null, mixed $default = null): string|array|null {
        if (empty($meta)) {
            return $this->metadata()->get();
        }

        if (is_array($meta) || is_object($meta) || $meta instanceof \Traversable) {
            $meta = collect($meta)->toArray();
            $data = $this->metadata()->whereIn('name', $meta)->get();

            return $data->isEmpty() ? $default : $data->map(fn ($m) => $m->value);
        }

        $value = $this->metadata()->whereName($meta)->first();

        return optional($value)->value ?? $default;
    }

    /**
     * get all metadata records as array.
     *
     * @param null|array<string>|string $meta
     *
     * @return array<string, mixed>
     */
    public function getMetaArray(mixed $meta = null): array {
        $metadata = $this->metadata();

        if (!empty($meta)) {
            if (is_array($meta) || is_object($meta) || $meta instanceof \Traversable) {
                $meta = collect($meta)->toArray();
                $metadata = $metadata->whereIn('name', $meta)->get()->map(fn ($m) => $m->value);
            } else {
                $metadata = $this->metadata()->whereName($meta)->first();
            }
        }

        return $metadata->pluck('value', 'name')->toArray();
    }

    /**
     * get all metadata records as object.
     *
     * @param null|array<string>|string $meta
     */
    public function getMetaObject(mixed $meta = null): object {
        return (object) $this->getMetaArray($meta);
    }

    /**
     * remove metadata record.
     *
     * @param array<string>|object|string $meta
     */
    public function removeMeta(mixed $meta = null): void {
        if (empty($meta)) {
            $this->metadata()->delete();

            return;
        }

        if (is_array($meta) || is_object($meta) || $meta instanceof \Traversable) {
            $meta = collect($meta)->toArray();

            $this->metadata()->whereIn('name', $meta)->delete();

            return;
        }

        $this->metadata()->whereName($meta)->delete();
    }

    /**
     * get record's metadata key-value array as an attribute.
     *
     * @return array<string, mixed>
     */
    public function getMetaAttribute(): array|Optional {
        return optional($this->metadata->pluck('value', 'name'));
    }

    /**
     * boot method for HasMetadata trait.
     */
    protected static function bootHasMetadata(): void {
        static::deleting(function(Model $model): void {
            $model->metadata()->delete();
        });
    }
}