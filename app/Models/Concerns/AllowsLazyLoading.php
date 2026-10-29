<?php

namespace App\Models\Concerns;

// Marks the hotel models. The application refuses "lazy loading" of relations (a slow-query guard, see
// App\Providers\AppServiceProvider), but the hotel accessors read relations on demand by design,
// so these models are exempted - and only these.
interface AllowsLazyLoading {}
