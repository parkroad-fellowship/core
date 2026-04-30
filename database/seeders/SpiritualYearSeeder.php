<?php

namespace Database\Seeders;

use App\Models\SpiritualYear;
use Illuminate\Database\Seeder;

class SpiritualYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $spiritualYears = [
            ['name' => '2023 - 2024'],
            ['name' => '2024 - 2025'],
            ['name' => '2025 - 2026'],
            ['name' => '2026 - 2027'],
            ['name' => '2027 - 2028'],
            ['name' => '2028 - 2029'],
            ['name' => '2029 - 2030'],
            ['name' => '2030 - 2031'],
            ['name' => '2031 - 2032'],
            ['name' => '2032 - 2033'],
            ['name' => '2033 - 2034'],
            ['name' => '2034 - 2035'],
            ['name' => '2035 - 2036'],
            ['name' => '2036 - 2037'],
            ['name' => '2037 - 2038'],
            ['name' => '2038 - 2039'],
            ['name' => '2039 - 2040'],
            ['name' => '2040 - 2041'],
            ['name' => '2041 - 2042'],
            ['name' => '2042 - 2043'],
            ['name' => '2043 - 2044'],
            ['name' => '2044 - 2045'],
            ['name' => '2045 - 2046'],
            ['name' => '2046 - 2047'],
            ['name' => '2047 - 2048'],
            ['name' => '2048 - 2049'],
            ['name' => '2049 - 2050'],
            ['name' => '2050 - 2051'],
            ['name' => '2051 - 2052'],
            ['name' => '2052 - 2053'],
            ['name' => '2053 - 2054'],
            ['name' => '2054 - 2055'],
            ['name' => '2055 - 2056'],
            ['name' => '2056 - 2057'],
            ['name' => '2057 - 2058'],
            ['name' => '2058 - 2059'],
            ['name' => '2059 - 2060'],
            ['name' => '2060 - 2061'],
            ['name' => '2061 - 2062'],
            ['name' => '2062 - 2063'],
            ['name' => '2063 - 2064'],
            ['name' => '2064 - 2065'],
            ['name' => '2065 - 2066'],
            ['name' => '2066 - 2067'],
            ['name' => '2067 - 2068'],
            ['name' => '2068 - 2069'],
            ['name' => '2069 - 2070'],
        ];

        foreach ($spiritualYears as $spiritualYear) {
            SpiritualYear::updateOrCreate($spiritualYear);
        }
    }
}
