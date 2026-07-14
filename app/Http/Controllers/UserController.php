<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Admin-only management of users, roles and manager assignments.
 * The whole resource is guarded by the 'role:admin' middleware in routes/web.php.
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::with('manager')->orderBy('role')->orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', [
            'user'     => new User(['role' => 'employee']),
            'managers' => $this->managers(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'       => ['required', Rule::in(['admin', 'manager', 'employee'])],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'manager')],
            'password'   => ['required', 'confirmed', Password::defaults()],
        ]);

        // Only employees report to a manager.
        if ($data['role'] !== 'employee') {
            $data['manager_id'] = null;
        }

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('users.index')->with('status', 'User created.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user'     => $user,
            'managers' => $this->managers()->where('id', '!=', $user->id),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'       => ['required', Rule::in(['admin', 'manager', 'employee'])],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'manager')],
            'password'   => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($data['role'] !== 'employee') {
            $data['manager_id'] = null;
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User deleted.');
    }

    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }

    protected function managers()
    {
        return User::where('role', 'manager')->orderBy('name')->get();
    }
}
