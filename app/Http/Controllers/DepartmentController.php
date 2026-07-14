<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::orderBy('name')->paginate(20);

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        return view('departments.create', ['department' => new Department()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $department = Department::create($data);

        return redirect()->route('departments.index')->with('status', "Department \"{$department->name}\" saved.");
    }

    public function edit(Department $department)
    {
        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $data = $this->validated($request, $department);

        $department->update($data);

        return redirect()->route('departments.index')->with('status', "Department \"{$department->name}\" updated.");
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')->with('status', 'Department deleted.');
    }

    // ---------------------------------------------------------------------

    protected function validated(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department?->id)],
        ], [
            'name.required' => 'Please enter a department name.',
            'name.unique'   => 'This department already exists.',
        ]);
    }
}
