<?php

namespace Database\Seeders;

use App\Models\Church;
use Illuminate\Database\Seeder;

class ChurchSeeder extends Seeder
{
    public function run(): void
    {
        $churches = [
            'ACK Kihingo Gathiga',
            'Anglican',
            'Beulah Springs Of Joy Church',
            'CFC',
            'Christ Freedom Chapel Ngong',
            'Christ Mission International For World Evangelism',
            'CITAM',
            'CITAM Buruburu',
            'CITAM Kikuyu',
            'CITAM Kikuyu Town Church',
            'CITAM Ngong',
            'CITAM Thika Town',
            'CITAM Valley Road',
            'City Light Center Church Rongai',
            'City of Praise Fellowship Ministry',
            'Deliverance Church',
            'Deliverance Church Kikuyu Road - Nairobi',
            'Deliverance Church Ngong Road Karen',
            'Deliverance Church Ngong Road Karen',
            'Deliverance Church Theta Ruiru',
            'Friends of Christ Covenant Centre',
            'Full Gospel Church',
            'GATE Gospel Mission',
            'GCE Fellowship',
            'GCE Kahawa Wendani',
            'Glorious Chapel Kahawa West',
            'Green Pastures Tabernacle Church - Hebron City',
            'Insight Mentorship Programme / Revival Sanctuary of Glory',
            'J.I.A.L Church Ruiru',
            'JCC Thika Road',
            'Jesus Glory Center Thogoto',
            'Jesus Kingdom City',
            'Kingdom Seekers Fellowship Thika Road',
            'KSF Thika',
            'Life Purpose',
            'Mamlaka Hill Chapel',
            'National Gospel Ministries',
            'Open House Church - Rongai',
            'P.C.E.A Riruta Satellite',
            'PCEA',
            'PCEA Langata',
            'Pentecostal Revival Church',
            'Prayers Beyond Boundaries Nairobi',
            'RCCG',
            'Ruach',
            'Ruach Tabernacle',
            'Ruach West Assemblies',
            'Showers of Grace Ministries Thika',
            'The Hebron Ministry',
            'Trinity Chapel Ruiru',
            'Parkroad Fellowship',
        ];

        foreach ($churches as $church) {
            Church::factory()
                ->create([
                    'name' => $church,
                ]);
        }
    }
}
