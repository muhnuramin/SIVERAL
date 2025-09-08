<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RencanaBelanjaController extends Controller
{
    public function index()
    {
        return Inertia('anggaran/RencanaBelanja');
    }
    public function viewSetPagu()
    {
        return Inertia('user/Pagu');
    }
    public function export()
    {
        return Inertia('anggaran/RencanaBelanjaExport');
    }
    public function show($id)
    {
        // minimal: pass the id to the page; replace with real data later
        return Inertia('anggaran/RincianBelanja', ['id' => $id]);
    }
}
