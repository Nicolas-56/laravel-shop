<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Services\CreateLead;
use Illuminate\Http\RedirectResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, CreateLead $createLead): RedirectResponse
    {
        $createLead->handle($request->validated());

        return redirect()
            ->route('home')
            ->with('success', 'Заявка отправлена. Мы свяжемся с вами в ближайшее время.');
    }
}
