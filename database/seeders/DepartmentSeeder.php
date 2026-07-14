<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Departments imported from the source system's dropdown (id => name).
     * IDs are preserved to match that system.
     */
    public function run(): void
    {
        $departments = [
            1  => 'President Office',
            2  => 'General Affair',
            3  => 'IT',
            4  => 'Bamawl',
            5  => 'Mandalay',
            56 => 'Developer Advocate',
            6  => 'Finance and Admin',
            7  => 'Human Resources',
            63 => 'HR & Admin',
            8  => 'BPO',
            9  => 'Infrastructure',
            10 => 'Contract',
            11 => 'Offshore',
            12 => 'Mobile',
            38 => 'Design',
            13 => 'Development',
            14 => 'Sales',
            59 => 'Contract Development III',
            25 => 'MPT',
            26 => 'Nipponn Foundation',
            61 => 'SCSK',
            27 => 'BPO Team',
            28 => 'Testing Team',
            51 => 'Low Code I (Team)',
            29 => 'Infra Team',
            30 => 'Contract Development II Team',
            47 => 'Contract Development I (Team)',
            60 => 'Contract Development III (Team)',
            31 => 'SST Team',
            32 => 'BRIDGESTONE Team',
            49 => 'Offshore Development I (Team)',
            50 => 'Offshore Development II (Team)',
            33 => 'Mobile Team',
            34 => 'Cake Team',
            35 => 'Laravel Team',
            36 => 'ERP Team',
            43 => 'Bamawl Development I (Team)',
            37 => 'Sales Team',
            39 => 'UI/UX Team',
            40 => 'Design Team',
            57 => 'Developer Advocate Department',
            58 => 'Developer Advocate Team',
        ];

        foreach ($departments as $id => $name) {
            Department::updateOrCreate(['id' => $id], ['name' => $name]);
        }
    }
}
