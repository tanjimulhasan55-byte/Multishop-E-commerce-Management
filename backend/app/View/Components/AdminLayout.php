<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    /**
     * @param  list<array{label: string, url?: string}>  $breadcrumbs
     */
    public function __construct(
        public string $title = 'Dashboard',
        public array $breadcrumbs = [],
    ) {}

    public function render(): View
    {
        return view('layouts.admin');
    }
}
