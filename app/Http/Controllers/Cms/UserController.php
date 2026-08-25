<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private function ensureAllowed(Request $request): void
    {
        abort_unless($request->user()->canManageUsers(), 403);
    }

    public function index(Request $request): View
    {
        $this->ensureAllowed($request);

        return view('cms.users.index', [
            'users' => User::query()
                ->where('role', '!=', 'developer')
                ->orderByRaw("CASE role WHEN 'admin' THEN 1 ELSE 2 END")
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensureAllowed($request);
        return view('cms.users.form', ['user' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAllowed($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:190', 'unique:users'],
            'role' => ['required', Rule::in(['admin', 'editor'])], 'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);
        User::query()->create([...$data, 'is_active' => true, 'email_verified_at' => now()]);
        return redirect()->route('cms.users.index')->with('success', 'User baru berhasil dibuat.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureAllowed($request);
        abort_if($user->role === 'developer' && $request->user()->role !== 'developer', 403);
        return view('cms.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAllowed($request);
        abort_if($user->role === 'developer' && $request->user()->role !== 'developer', 403);
        $roles = $request->user()->role === 'developer' ? ['developer', 'admin', 'editor'] : ['admin', 'editor'];
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in($roles)], 'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        if (blank($data['password'] ?? null)) unset($data['password']);
        if ($user->is($request->user())) $data['is_active'] = true;
        $user->update($data);
        return redirect()->route('cms.users.index')->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureAllowed($request);
        abort_if($user->is($request->user()) || $user->role === 'developer', 422, 'Akun ini tidak dapat dihapus.');
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}
