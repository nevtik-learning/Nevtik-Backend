<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\MengukurWaktu;
use App\Http\Controllers\Controller;
use App\Http\Resources\PertemuanResource;
use App\Models\Pertemuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PertemuanController extends Controller
{
    use MengukurWaktu;

    private function batasiPengurus(Request $request, Pertemuan $pertemuan): void
    {
        $me = $request->user();
    
        abort_if(
            $me->role === 'pengurus' && $pertemuan->pengurus_id !== $me->id,
            403, 'hanya boleh mengurus pertemuan buatan sendiri'
        );
    }
  
    public function index(Request $request)
    {
        $t = $this->ukurMulai();
        $request->validate(['divisi_id' => 'nullable|integer']);

        // kunci cache hanya dari parameter yang dikenal
        $key = 'pertemuan:index:v' . Cache::get('pertemuan:ver', 0)
             . ':' . md5(json_encode($request->only('divisi_id', 'page')));

        $data = Cache::remember($key, 300, fn () => Pertemuan::query()
            ->with('divisi:id,nama', 'pengurus:id,name')
            ->when($request->divisi_id, fn ($q, $v) => $q->where('divisi_id', $v))
            ->latest('mulai')
            ->paginate(20));

        return PertemuanResource::collection($data)
            ->additional(['status' => true] + $this->debug($t));
    }

    public function store(Request $request)
    {
        $t = $this->ukurMulai();

        $data = $request->validate([
            'divisi_id' => 'nullable|exists:divisi,id',
            'role'      => 'nullable|in:siswa,pengurus',
            'nama'      => 'required|string|max:100',
            'mode'      => 'required|in:online,offline',
            'latitude'  => 'exclude_unless:mode,offline|required|numeric|between:-90,90',
            'longitude' => 'exclude_unless:mode,offline|required|numeric|between:-180,180',
            'distance'  => 'exclude_unless:mode,offline|required|integer|min:1',
            'mulai'     => 'required|date',
            'selesai'   => 'required|date|after:mulai',
        ]);

        $user = $request->user();

        // pengurus: wajib punya divisi dan hanya boleh untuk divisinya sendiri
        if ($user->role === 'pengurus') {
            abort_if(
                ! $user->divisi_id || (int) ($data['divisi_id'] ?? 0) !== (int) $user->divisi_id,
                403, 'pengurus hanya boleh membuat pertemuan untuk divisinya sendiri'
            );
        }

        $pertemuan = Pertemuan::buat($data + ['pengurus_id' => $user->id]);

        return (new PertemuanResource($pertemuan->load('divisi:id,nama', 'pengurus:id,name')))
            ->additional(['status' => true, 'message' => 'pertemuan berhasil dibuat'] + $this->debug($t))
            ->response()->setStatusCode(201);
    }

    public function show(Pertemuan $pertemuan)
    {
        $t = $this->ukurMulai();

        return (new PertemuanResource($pertemuan->load('divisi:id,nama', 'pengurus:id,name')))
            ->additional(['status' => true] + $this->debug($t));
    }

    public function update(Request $request, Pertemuan $pertemuan)
    {
        $t = $this->ukurMulai();
        $this->batasiPengurus($request, $user);
      
        abort_if(
            $request->user()->role === 'pengurus' && $pertemuan->pengurus_id !== $request->user()->id,
            403, 'hanya boleh mengubah pertemuan buatan sendiri'
        );

        // mode, divisi, role tidak bisa diubah: peserta sudah dibentuk trigger
        $data = $request->validate([
            'nama'      => 'sometimes|string|max:100',
            'mulai'     => 'sometimes|date',
            'selesai'   => 'sometimes|date',
            'latitude'  => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'distance'  => 'sometimes|integer|min:1',
        ]);

        // bandingkan dengan nilai yang ada kalau salah satunya tidak dikirim
        $mulai   = \Carbon\Carbon::parse($data['mulai'] ?? $pertemuan->mulai);
        $selesai = \Carbon\Carbon::parse($data['selesai'] ?? $pertemuan->selesai);
        abort_if($selesai->lte($mulai), 422, 'selesai harus setelah mulai');

        if ($pertemuan->mode === 'online') {
            unset($data['latitude'], $data['longitude'], $data['distance']);
        }

        $pertemuan->update($data);

        return (new PertemuanResource($pertemuan->load('divisi:id,nama', 'pengurus:id,name')))
            ->additional(['status' => true, 'message' => 'pertemuan berhasil diupdate'] + $this->debug($t));
    }

      public function destroy(Request $request, Pertemuan $pertemuan)
      {
        
         $this->batasiPengurus($request, $user);
        
          abort_if(
              $request->user()->role === 'pengurus' && $pertemuan->pengurus_id !== $request->user()->id,
              403, 'hanya boleh menghapus pertemuan buatan sendiri'
          );
      
          $pertemuan->delete(); // absensinya ikut terhapus (cascade)
      
          return response()->json(['status' => true, 'message' => 'pertemuan berhasil dihapus']);
      }
}