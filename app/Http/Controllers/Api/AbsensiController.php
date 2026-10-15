<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\MengukurWaktu;
use App\Http\Controllers\Controller;
use App\Http\Resources\AbsensiResource;
use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbsensiController extends Controller
{
    use MengukurWaktu;

    // rekap: GET /absensi?mode=bulanan&bulan=10&tahun=2026&divisi_id=4
    public function index(Request $request)
    {
        $t = $this->ukurMulai();

        $request->validate([
            'mode'      => 'nullable|in:mingguan,bulanan,tahunan,custom',
            'bulan'     => 'nullable|integer|between:1,12',
            'tahun'     => 'nullable|integer|between:2000,2100',
            'divisi_id' => 'nullable|integer',
            'start'     => 'required_if:mode,custom|nullable|date',
            'end'       => 'required_if:mode,custom|nullable|date|after_or_equal:start',
        ]);

        $mode = $request->input('mode', 'mingguan');
        [$awal, $akhir, $pesan] = $this->periode($request, $mode);

        $base = DB::table('v_absensi')
            ->whereBetween('mulai', [$awal, $akhir])
            ->when($request->divisi_id, fn ($q, $v) => $q->where('user_divisi_id', $v));

        // query 1: rekap per user
        $perUser = (clone $base)->selectRaw("
                user_id, user_name AS nama, user_email AS email,
                COUNT(*) FILTER (WHERE status = 'hadir') AS hadir,
                COUNT(*) FILTER (WHERE status = 'izin')  AS izin,
                COUNT(*) FILTER (WHERE status = 'sakit') AS sakit,
                COUNT(*) FILTER (WHERE status = 'alpha') AS alpha
            ")
            ->groupBy('user_id', 'user_name', 'user_email')
            ->orderBy('nama')
            ->get();

        // query 2: jumlah pertemuan
        $totalPertemuan = (clone $base)->distinct()->count('pertemuan_id');

        // rekap global dijumlah dari hasil query 1, tanpa query tambahan
        return response()->json(array_merge([
            'status'          => true,
            'message'         => $pesan,
            'periode'         => ['start' => $awal->toDateString(), 'end' => $akhir->toDateString()],
            'total_pertemuan' => $totalPertemuan,
            'rekap_global'    => [
                'hadir' => (int) $perUser->sum('hadir'),
                'izin'  => (int) $perUser->sum('izin'),
                'sakit' => (int) $perUser->sum('sakit'),
                'alpha' => (int) $perUser->sum('alpha'),
            ],
            'rekap_per_user'  => $perUser,
        ], $this->debug($t)));
    }

    // daftar baris satu pertemuan, untuk koreksi manual
    public function detail(Request $request)
    {
        $t = $this->ukurMulai();
        $request->validate(['pertemuan_id' => 'required|string|size:26']);

        $rows = DB::table('v_absensi')
            ->where('pertemuan_id', $request->pertemuan_id)
            ->orderBy('user_name')
            ->get(['id', 'user_id', 'user_name', 'status', 'jarak', 'waktu_absen']);

        return response()->json(array_merge(['status' => true, 'data' => $rows], $this->debug($t)));
    }

      // koreksi manual, misal siswa lupa absen
    public function update(Request $request, Absensi $absensi)
    {
      $t = $this->ukurMulai();
    
      $data = $request->validate([
          'status'     => 'required|in:hadir,izin,sakit,alpha',
          'keterangan' => 'required_if:status,izin,sakit|nullable|string|max:255',
      ]);
    
      if ($request->user()->role === 'pengurus') {
          $punya = $absensi->pertemuan()->where('pengurus_id', $request->user()->id)->exists();
          abort_unless($punya, 403, 'hanya boleh mengoreksi pertemuan buatan sendiri');
      }
    
      $absensi->update([
          'status'      => $data['status'],
          'keterangan'  => in_array($data['status'], ['izin', 'sakit']) ? $data['keterangan'] : null,
          'waktu_absen' => $data['status'] === 'alpha' ? null : now(),
      ]);
    
      return (new AbsensiResource($absensi->load('user:id,name,divisi_id')))
          ->additional(['status' => true, 'message' => 'absensi berhasil diupdate'] + $this->debug($t));
    }

    public function destroy(Absensi $absensi)
    {
        $absensi->delete();

        return response()->json(['status' => true, 'message' => 'absensi berhasil dihapus']);
    }

    private function periode(Request $request, string $mode): array
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        return match ($mode) {
            'bulanan' => [Carbon::create($tahun, $bulan, 1)->startOfMonth(), Carbon::create($tahun, $bulan, 1)->endOfMonth(), 'data rekap bulanan'],
            'tahunan' => [Carbon::create($tahun, 1, 1)->startOfYear(), Carbon::create($tahun, 1, 1)->endOfYear(), 'data rekap tahunan'],
            'custom'  => [Carbon::parse($request->start)->startOfDay(), Carbon::parse($request->end)->endOfDay(), 'data rekap custom'],
            default   => [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY), 'data rekap minggu ini'],
        };
    }
}