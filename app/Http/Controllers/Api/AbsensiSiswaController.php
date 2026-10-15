<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\MengukurWaktu;
use App\Http\Controllers\Controller;
use App\Http\Resources\AbsensiSiswaResource;
use App\Models\Absensi;
use App\Models\Pertemuan;
use Illuminate\Http\Request;
use Iroid\LaravelHaversine\Haversine;

class AbsensiSiswaController extends Controller
{
    use MengukurWaktu;

    // gerbang tunggal: dari QR (id) atau input kode
    public function resolve(Request $request)
    {
        $t = $this->ukurMulai();

        $request->validate([
            'id'   => 'required_without:kode|string|size:26',
            'kode' => 'required_without:id|digits:5',
        ]);

        $p = Pertemuan::select('id', 'mode')
            ->when($request->id, fn ($q, $v) => $q->where('id', $v))
            ->when($request->kode, fn ($q, $v) => $q->where('kode', $v))
            ->firstOrFail();

        return response()->json(array_merge([
            'status' => true,
            'data'   => ['id' => $p->id, 'mode' => $p->mode],
        ], $this->debug($t)));
    }

    public function masuk(Request $request, Pertemuan $pertemuan)
    {
        $t = $this->ukurMulai();

        if (! now()->between($pertemuan->mulai, $pertemuan->selesai)) {
            return response()->json(['status' => false, 'message' => 'di luar waktu absensi'], 403);
        }

        $jarak = $lat = $lng = null;

        if ($pertemuan->mode === 'offline') {
            $jarak = $this->validasiLokasi($request, $pertemuan);
            $lat = $request->latitude;
            $lng = $request->longitude;
        }

        // atomik: hanya berhasil kalau masih alpha, jadi tidak bisa dobel
        $ok = Absensi::where('pertemuan_id', $pertemuan->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'alpha')
            ->update([
                'status'      => 'hadir',
                'latitude'    => $lat,
                'longitude'   => $lng,
                'jarak'       => $jarak,
                'waktu_absen' => now(),
                'updated_at'  => now(),
            ]);

        if (! $ok) {
            return $this->gagalUpdate($pertemuan, $request);
        }

        return response()->json(array_merge(
            ['status' => true, 'message' => 'absen berhasil', 'jarak' => $jarak],
            $this->debug($t)
        ));
    }
  
    public function riwayat(Request $request)
    {
        $t = $this->ukurMulai();

        $data = Absensi::query()
            ->with('pertemuan:id,nama,mode,mulai')
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(20);

        return AbsensiSiswaResource::collection($data)
            ->additional(['status' => true] + $this->debug($t));
    }

    private function validasiLokasi(Request $request, Pertemuan $pertemuan): int
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ], ['required' => 'lokasi wajib untuk pertemuan offline']);

        $jarak = (int) round(Haversine::distance(
            $pertemuan->latitude, $pertemuan->longitude,
            $request->latitude, $request->longitude,
            'meters'
        ));

        if ($jarak > $pertemuan->distance) {
            abort(403, "terlalu jauh ({$jarak} m, maksimal {$pertemuan->distance} m)");
        }

        return $jarak;
    }

    // query tambahan hanya di jalur gagal, supaya pesannya jelas
    private function gagalUpdate(Pertemuan $pertemuan, Request $request)
    {
        $status = Absensi::where('pertemuan_id', $pertemuan->id)
            ->where('user_id', $request->user()->id)
            ->value('status');

        $pesan = $status === null ? 'kamu bukan peserta pertemuan ini' : 'kamu sudah tercatat: ' . $status;

        return response()->json(['status' => false, 'message' => $pesan], 403);
    }
}