<?php

namespace App\Services;

use App\Models\Lead;

class LeadService
{
    public function create(array $payload): Lead
    {
        $payload['status'] = 'new';

        return Lead::query()->create($payload);
    }
}
