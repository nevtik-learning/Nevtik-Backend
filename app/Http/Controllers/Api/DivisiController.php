<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DivisiResource;
use App\Models\Divisi;
use Illuminate\Http\Request;

class DivisiController extends Controller
{
    public function index(Request $request)
    {
        $divisi = Divisi::withCount('users')
            ->with('users:id,name,divisi_id')
            ->when($request->search, fn ($q, $v) => $q->where('nama', 'ilike', "%{$v}%"))
            ->orderBy('nama')
            ->get();
    
        return DivisiResource::collection($divisi)
            ->additional(['status' => true]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100|unique:divisi,nama',
        ]);

        return (new DivisiResource(Divisi::create($data)))
            ->additional(['status' => true, 'message' => 'divisi berhasil ditambahkan'])
            ->response()->setStatusCode(201);
    }

    public function show(Divisi $divisi)
    {
        return (new DivisiResource($divisi->loadCount('users')))
            ->additional(['status' => true]);
    }

    public function update(Request $request, Divisi $divisi)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100|unique:divisi,nama,' . $divisi->id,
        ]);

        $divisi->update($data);

        return (new DivisiResource($divisi))
            ->additional(['status' => true, 'message' => 'divisi berhasil diupdate']);
    }

    public function destroy(Divisi $divisi)
    {
        // pertemuan memakai restrictOnDelete, jadi cek dulu supaya pesannya jelas
        if ($divisi->pertemuan()->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'divisi masih punya pertemuan, tidak bisa dihapus',
            ], 409);
        }

        $divisi->delete();

        return response()->json(['status' => true, 'message' => 'divisi berhasil dihapus']);
    }
}