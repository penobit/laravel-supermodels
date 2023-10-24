<?php

namespace Penobit\SuperModels;

trait HasLogs {
    /**
     * get resource logs.
     *
     * @return MorphMany
     */
    public function logs() {
        return $this->morphMany(
            config('supermodels.models.log'),
            'model'
        )->orderBy('created_at', 'desc');
    }

    /**
     * add a new log to resource.
     *
     * @param ?array<string, string> $params
     */
    public function log(string $action, string $content, ?array $params = [], ?string $ip = null, ?string $userAgent = null): static {
        $userId = auth()->id();

        if (empty($userId)) {
            return $this;
        }

        if (empty($ip)) {
            $ip = request()->ip();
        }

        if (empty($userAgent)) {
            $userAgent = request()->userAgent();
        }

        $this->logs()->create([
            'user_id' => $userId,
            'action' => $action,
            'content' => $content,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'params' => $params,
        ]);

        return $this;
    }

    /**
     * boot method for HasLogs trait.
     */
    protected static function bootHasLogs(): void {
        static::deleting(function($model): void {
            $model->logs()->delete();
        });
    }
}
