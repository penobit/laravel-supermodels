<?php

namespace Penobit\SuperModels;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Optional;

trait HasMeta {
    protected array $cachedMeta = [];

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
    public function setMeta(mixed $meta, mixed $value = null): static {
        if (is_array($meta) || is_object($meta) || $meta instanceof \Traversable) {
            $meta = collect($meta);
            $upsert = [];

            foreach ($meta as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    $v = serialize($v);
                }

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
            $this->reloadMetaCache();

            return $this;
        }

        if (is_array($value) || is_object($value) || $value instanceof \Traversable) {
            $value = serialize($value);
        }

        $this->metadata()->updateOrCreate(['name' => $meta], ['value' => $value]);
        $this->reloadMetaCache();

        return $this;
    }

    /**
     * get record's metadata by name.
     *
     * @param ?string $meta
     *
     * @return null|array<string, mixed>|string
     */
    public function getMeta(mixed $meta = null, mixed $default = null, bool $cache = true): mixed {
        if (!$cache) {
            $this->reloadMetaCache();
        }

        $metadata = collect($this->cacheMeta());

        if (empty($meta)) {
            return $metadata;
        }

        if (is_array($meta) || is_object($meta) || $meta instanceof \Traversable) {
            $meta = collect($meta)->toArray();

            return $metadata->filter(fn ($m, $k) => in_array($k, $meta))->map(fn ($m) => $this->parseMetadataValue($m))->toArray();
        }

        return $this->parseMetadataValue($metadata->get($meta, $default));
    }

    /**
     * get all metadata records as array.
     *
     * @param null|array<string>|string $meta
     *
     * @return array<string, mixed>
     */
    public function getMetaArray(mixed $meta = null, bool $cache = true): array {
        $metadata = $this->cacheMeta($cache);

        if (!empty($meta)) {
            if (is_array($meta) || is_object($meta) || $meta instanceof \Traversable) {
                $meta = collect($meta)->toArray();
                // $metadata = $metadata->whereIn('name', $meta);
                // $metadata = $metadata->map(fn ($m) => $m->value);
                $res = [];
                foreach ($meta as $key) {
                    $res[$key] = $metadata[$key];
                }

                return $res;
            }

            return $metadata[$meta] ?? null;
        }

        $res = [];
        foreach ($metadata as $key => $value) {
            $res[$key] = $this->parseMetadataValue($value);
        }

        return $res;
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
    public function removeMeta(mixed $meta = null): static {
        if (empty($meta)) {
            $this->metadata()->delete();
            $this->reloadMetaCache();

            return $this;
        }

        if (is_array($meta) || is_object($meta) || $meta instanceof \Traversable) {
            $meta = collect($meta)->toArray();

            $this->metadata()->whereIn('name', $meta)->delete();
            $this->reloadMetaCache();

            return $this;
        }

        $this->metadata()->whereName($meta)->delete();
        $this->reloadMetaCache();

        return $this;
    }

    /**
     * get record's metadata key-value array as an attribute.
     *
     * @return array<string, mixed>
     */
    public function getMetaAttribute(): Optional {
        return optional($this->getMetaObject());
    }

    /**
     * get record's metadata key-value array as an attribute.
     *
     * @return array<string, mixed>
     */
    public function getMetaArrayAttribute(): ?array {
        return $this->cacheMeta() ?? [];
    }

    /**
     * cache current model's metada to avoid extra db connections.
     *
     * @return array<string, mixed> cached metadata
     */
    public function cacheMeta($cache = true): array {
        if (empty($this->cachedMeta) || !$cache) {
            $cachedMeta = $this->metadata()->pluck('value', 'name')->toArray();
            $this->cachedMeta = collect($cachedMeta)->map(fn ($m) => $this->parseMetadataValue($m))->toArray();
        }

        return $this->cachedMeta;
    }

    /**
     * reload model's cached metadata.
     *
     * @return array<string, mixed> cached metadata
     */
    public function reloadMetaCache(): array {
        return $this->cachedMeta = $this->getMetaArray(null, false);
    }

    /**
     * Parse saved meta value to it's original type.
     */
    protected function parseMetadataValue(mixed $value, mixed $default = null): mixed {
        if (empty($value)) {
            return $default ?? null;
        }

        if (is_bool($value) || in_array(needle: $value, haystack: ['true', 'false', '1', '0', 'yes', 'no'], strict: true)) {
            return is_bool($value) ? $value : (
                in_array(needle: $value, haystack: ['true', '1', 'yes'], strict: true)
            );
        }

        if (is_string($value)) {
            $unserialized = @unserialize($value);

            if (false !== $unserialized) {
                return $unserialized;
            }

            if (preg_match('/^[\[\{]/', $value) !== false) {
                $jsonDecoded = @json_decode(json: $value);

                if (false !== $jsonDecoded && (is_array($jsonDecoded) || is_object($jsonDecoded))) {
                    return $jsonDecoded;
                }
            }
        }

        return $value;
    }

    /**
     * boot method for HasMetadata trait.
     */
    protected static function bootHasMetadata(): void {
        static::deleting(function($model): void {
            $model->metadata()->delete();
        });
    }
}
