<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Badge extends Component
{
    public string $type;
    public string $text;

    public function __construct(string $type = 'gray', string $text = '')
    {
        $this->type = $type;
        $this->text = $text;
    }

    public function render()
    {
        return view('components.badge');
    }
}
