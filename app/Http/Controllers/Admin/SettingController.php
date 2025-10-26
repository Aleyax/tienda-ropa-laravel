<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'wholesale_first_order_min' => 'required|numeric|min:0',
            'show_out_of_stock' => 'nullable|boolean',
        ]);

        Setting::setValue('wholesale_first_order_min', $data['wholesale_first_order_min']);
        Setting::setValue('show_out_of_stock', $request->boolean('show_out_of_stock'));

        return back()->with('success', 'Configuraciones guardadas correctamente.');
    }
}
