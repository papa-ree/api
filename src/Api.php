<?php

namespace Bale\Api;

use Bale\Api\Services\TokenManager;

class Api
{
    public function tokens(): TokenManager
    {
        return app(TokenManager::class);
    }
}
