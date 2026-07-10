<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AkunPengurusController extends Controller
{
    /**
     * Daftar akun pengurus. Filter: ?role=kasir, ?status=aktif|nonaktif, ?search=nama/email.
     */
    public function index(Request $request): JsonResponse
    {
        $pengurus = User::query()
            ->whereIn('role', User::PENGURUS_ROLES)
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->query('status'), fn ($q, $status) => $q->where('is_active', $status === 'aktif'))
            ->when($request->query('search'), fn ($q, $search) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'message' => 'Daftar akun pengurus.',
            'data' => $pengurus,
        ]);
    }

    /**
     * Buat akun pengurus baru (Kasir, Bendahara, Logistik, Sekretaris, Pengawas, Ketua).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'alamat' => ['required', 'string', 'max:1000'],
            'no_wa' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'role' => ['required', Rule::in(User::PENGURUS_ROLES)],
        ], [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'no_wa.regex' => 'Nomor WA tidak valid (contoh: 081234567890).',
            'role.in' => 'Role harus salah satu dari: '.implode(', ', User::PENGURUS_ROLES).'.',
        ]);

        $user = User::create($validated + ['is_active' => true]);

        return response()->json([
            'message' => 'Akun pengurus berhasil dibuat.',
            'data' => $user,
        ], 201);
    }

    /**
     * Detail satu akun pengurus.
     */
    public function show(User $user): JsonResponse
    {
        $this->ensurePengurus($user);

        return response()->json([
            'message' => 'Detail akun pengurus.',
            'data' => $user,
        ]);
    }

    /**
     * Edit akun pengurus (semua field opsional; password hanya diganti jika dikirim).
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->ensurePengurus($user);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'nik' => ['sometimes', 'digits:16', Rule::unique('users', 'nik')->ignore($user->id)],
            'alamat' => ['sometimes', 'string', 'max:1000'],
            'no_wa' => ['sometimes', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'role' => ['sometimes', Rule::in(User::PENGURUS_ROLES)],
        ], [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'no_wa.regex' => 'Nomor WA tidak valid (contoh: 081234567890).',
            'role.in' => 'Role harus salah satu dari: '.implode(', ', User::PENGURUS_ROLES).'.',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Akun pengurus berhasil diperbarui.',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Aktifkan / nonaktifkan akun. Nonaktif = semua token dicabut, tidak bisa login.
     */
    public function setStatus(Request $request, User $user): JsonResponse
    {
        $this->ensurePengurus($user);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->id === $request->user()->id && ! $validated['is_active']) {
            return response()->json(['message' => 'Anda tidak dapat menonaktifkan akun sendiri.'], 422);
        }

        $user->update(['is_active' => $validated['is_active']]);

        if (! $validated['is_active']) {
            $user->tokens()->delete(); // putus sesi yang sedang berjalan
        }

        return response()->json([
            'message' => $validated['is_active'] ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Hapus akun pengurus (soft delete).
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->ensurePengurus($user);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Anda tidak dapat menghapus akun sendiri.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Akun pengurus berhasil dihapus.']);
    }

    /**
     * Endpoint ini hanya mengelola akun pengurus — anggota & admin dikelola di tempat lain.
     */
    private function ensurePengurus(User $user): void
    {
        abort_unless(in_array($user->role, User::PENGURUS_ROLES, true), 404, 'Akun pengurus tidak ditemukan.');
    }
}
