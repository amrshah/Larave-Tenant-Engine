<?php

namespace Amrshah\TenantEngine\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;

class DebugInitTenancy extends InitializeTenancyByPath
{
    // Uses standard Stancl Path Identity logic
    // Expects path to start with tenant identifier
}
