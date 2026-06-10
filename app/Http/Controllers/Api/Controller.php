<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesApiListing;
use App\Http\Controllers\Api\Concerns\HandlesBulkActions;
use App\Http\Controllers\Controller as BaseController;

abstract class Controller extends BaseController
{
    use HandlesApiListing, HandlesBulkActions;
}
