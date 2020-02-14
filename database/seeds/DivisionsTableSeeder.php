<?php

use Illuminate\Database\Seeder;
use App\Models\EmpDivision;

class DivisionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $divisions = [
            'Technical Services Division',
            'Finance and Administrative Services',
            'Office of the Regional Director',
            'USTC - Baguio',
            'PSTC - Mountain Province',
            'PSTC - Abra',
            'PSTC - Apayao',
            'PSTC - Benguet',
            'PSTC - Ifugao',
            'PSTC - Kalinga'
        ];

        foreach ($divisions as $div) {
            $division = new EmpDivision;
            $division->division_name = $div;
            $division->save();
        }
    }
}
