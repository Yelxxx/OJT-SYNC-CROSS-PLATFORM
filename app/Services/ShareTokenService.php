<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShareTokenService
{
    /**
     * Ensure the given model has a non-null, unique share_token.
     * Called lazily (observers / controllers) so existing rows are
     * covered without a data migration.
     */
    public static function ensure(Model $model): string
    {
        if (! $model->share_token) {
            $model->forceFill(['share_token' => Str::random(40)])->save();

            $model->refresh();
        }

        return $model->share_token;
    }
}
