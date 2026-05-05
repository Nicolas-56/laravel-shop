<?php

namespace App\Services;

use App\Models\Lead;

class CreateLead
{
    public function handle(array $payload): Lead
    {
        $payload['status'] = 'new';

        return Lead::query()->create($payload);
    }
}
