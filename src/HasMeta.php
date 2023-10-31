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
            $data = $metadata->whereIn('name', $meta)->first();

            return $data->isEmpty() ? $default : $data->map(fn ($m) => $m->value);
        }

        return $metadata[$meta] ?? $default;
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
        // dd($metadata->get()->pluck('value', 'name'), $meta);

        return $metadata->get()->each(function($m) {
            if (preg_match('/^[\[\{]/', $m->value) !== false) {
                try {
                    $m->value = unserialize($m->value);
                } catch (\Throwable $e) {
                    $m->value = $m->value;
                }
            }

            return $m;
        })->pluck('value', 'name')->toArray();
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
        return optional((object) $this->cacheMeta());
    }

    /**
     * get record's metadata key-value array as an attribute.
     *
     * @return array<string, mixed>
     */
    public function getMetaArrayAttribute(): Optional {
        return optional($this->cacheMeta());
    }

    /**
     * cache current model's metada to avoid extra db connections.
     *
     * @return array<string, mixed> cached metadata
     */
    public function cacheMeta(): array {
        if (empty($this->cachedMeta)) {
            return $this->cachedMeta = $this->getMetaArray();
        }

        return $this->cachedMeta;
    }

    /**
     * reload model's cached metadata.
     *
     * @return array<string, mixed> cached metadata
     */
    public function reloadMetaCache(): array {
        return $this->cachedMeta = $this->getMetaArray();
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
