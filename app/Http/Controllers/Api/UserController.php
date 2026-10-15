<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

      
    private function batasiPengurus(Request $request, User $target): void
    {
              $me = $request->user();
          
              if ($me->role !== 'pengurus') {
                  return;
              }
          
              abort_unless(
                  $me->divisi_id && $target->divisi_id === $me->divisi_id && $target->role === 'siswa',
                  403, 'hanya boleh mengurus siswa di divisinya sendiri'
              );
    }


  
    public function index(Request $request)
    {
        $users = User::with('divisi:id,nama')
            ->when($request->role, fn ($q, $v) => $q->where('role', $v))
            ->when($request->divisi_id, fn ($q, $v) => $q->where('divisi_id', $v))
            ->when($request->search, fn ($q, $v) => $q->where('name', 'ilike', "%{$v}%"))
            ->when($request->user()->role === 'pengurus', fn ($q) => $q->where('divisi_id', $request->user()->divisi_id))
            ->orderBy('name')
            ->paginate(50);

        return UserResource::collection($users)->additional(['status' => true]);
    }
    
    public function store(Request $request)
    {
      $data = $request->validate([
          'name'      => 'required|string|max:100',
          'password'  => 'required|string|min:8',
          'role'      => ['required', Rule::in(['admin', 'guru', 'pengurus', 'siswa'])],
          'divisi_id' => 'required_if:role,siswa|nullable|exists:divisi,id',
      ]);

      if ($request->user()->role === 'pengurus') {
        $data['role'] = 'siswa';
        $data['divisi_id'] = $request->user()->divisi_id;
      }
    
      $data['email'] = User::buatEmail($data['name']);
    
      $user = User::create($data)->load('divisi:id,nama');
    
      return (new UserResource($user))
          ->additional(['status' => true, 'message' => 'user berhasil ditambahkan'])
          ->response()->setStatusCode(201);
    }

    public function show(User $user)
    {
        return (new UserResource($user->load('divisi:id,nama')))
            ->additional(['status' => true]);
    }

    public function update(Request $request, User $user)
    {

      $this->batasiPengurus($request, $user);
      
      $data = $request->validate([
          'name'      => 'sometimes|string|max:100',
          'password'  => 'sometimes|string|min:8',
          'role'      => ['sometimes', Rule::in(['admin', 'guru', 'pengurus', 'siswa'])],
          'divisi_id' => 'nullable|exists:divisi,id',
      ]);
    
      // email ikut berubah hanya kalau namanya benar-benar berganti
      if (isset($data['name']) && $data['name'] !== $user->name) {
          $data['email'] = User::buatEmail($data['name'], $user->id);
      }
    
      $user->update($data);
    
      if ($request->hasAny(['password', 'role'])) {
          $user->tokens()->delete();
      }
    
      return (new UserResource($user->load('divisi:id,nama')))
          ->additional(['status' => true, 'message' => 'user berhasil diupdate']);
    }

    public function destroy(User $user ,Request $request)
    {
      $this->batasiPengurus($request, $user);
      
        $user->delete(); // absensi ikut terhapus (cascade)

        return response()->json(['status' => true, 'message' => 'user berhasil dihapus']);
    }
}