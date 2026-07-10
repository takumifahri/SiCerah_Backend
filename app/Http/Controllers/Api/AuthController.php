<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrasi anggota baru: data identitas + upload KTP.
     * Data finansial diinput terpisah oleh Bendahara.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'alamat' => ['required', 'string', 'max:1000'],
            'no_wa' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'ktp' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'no_wa.regex' => 'Nomor WA tidak valid (contoh: 081234567890).',
            'ktp.max' => 'Ukuran file KTP maksimal 2MB.',
        ]);

        $ktpPath = $request->file('ktp')->store('ktp');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'nik' => $validated['nik'],
            'alamat' => $validated['alamat'],
            'no_wa' => $validated['no_wa'],
            'ktp_path' => $ktpPath,
            'role' => 'anggota',
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil. KTP akan diverifikasi untuk domisili desa.',
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dinonaktifkan. Hubungi Administrator.',
            ]);
        }

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
