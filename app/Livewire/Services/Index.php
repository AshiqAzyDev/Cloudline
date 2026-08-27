<?php

namespace App\Livewire\Services;

use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Services')]
class Index extends Component
{
    public function render()
    {
        $this->authorize('viewAny', Service::class);

        return view('livewire.services.index', [
            'services' => Service::query()->with('billingEntity')->orderBy('name')->get(),
        ]);
    }
}
