<?php

return [
    'api' => [
        'url' => env('WEATHER_API_URL', 'https://api.tomorrow.io/v4'),
        'apiKey' => env('WEATHER_API_KEY'),
        'units' => env('WEATHER_API_UNITS', 'metric'),
    ],
    'codes' => [
        [
            'key' => '0',
            'value' => 'Unknown',
        ],
        [
            'key' => '1000',
            'value' => 'Clear, Sunny',
        ],
        [
            'key' => '1100',
            'value' => 'Mostly Clear',
        ],
        [
            'key' => '1101',
            'value' => 'Partly Cloudy',
        ],
        [
            'key' => '1102',
            'value' => 'Mostly Cloudy',
        ],
        [
            'key' => '1001',
            'value' => 'Cloudy',
        ],
        [
            'key' => '1103',
            'value' => 'Partly Cloudy and Mostly Clear',
        ],
        [
            'key' => '2100',
            'value' => 'Light Fog',
        ],
        [
            'key' => '2101',
            'value' => 'Mostly Clear and Light Fog',
        ],
        [
            'key' => '2102',
            'value' => 'Partly Cloudy and Light Fog',
        ],
        [
            'key' => '2103',
            'value' => 'Mostly Cloudy and Light Fog',
        ],
        [
            'key' => '2106',
            'value' => 'Mostly Clear and Fog',
        ],
        [
            'key' => '2107',
            'value' => 'Partly Cloudy and Fog',
        ],
        [
            'key' => '2108',
            'value' => 'Mostly Cloudy and Fog',
        ],
        [
            'key' => '2000',
            'value' => 'Fog',
        ],
        [
            'key' => '4204',
            'value' => 'Partly Cloudy and Drizzle',
        ],
        [
            'key' => '4203',
            'value' => 'Mostly Clear and Drizzle',
        ],
        [
            'key' => '4205',
            'value' => 'Mostly Cloudy and Drizzle',
        ],
        [
            'key' => '4000',
            'value' => 'Drizzle',
        ],
        [
            'key' => '4200',
            'value' => 'Light Rain',
        ],
        [
            'key' => '4213',
            'value' => 'Mostly Clear and Light Rain',
        ],
        [
            'key' => '4214',
            'value' => 'Partly Cloudy and Light Rain',
        ],
        [
            'key' => '4215',
            'value' => 'Mostly Cloudy and Light Rain',
        ],
        [
            'key' => '4209',
            'value' => 'Mostly Clear and Rain',
        ],
        [
            'key' => '4208',
            'value' => 'Partly Cloudy and Rain',
        ],
        [
            'key' => '4210',
            'value' => 'Mostly Cloudy and Rain',
        ],
        [
            'key' => '4001',
            'value' => 'Rain',
        ],
        [
            'key' => '4211',
            'value' => 'Mostly Clear and Heavy Rain',
        ],
        [
            'key' => '4202',
            'value' => 'Partly Cloudy and Heavy Rain',
        ],
        [
            'key' => '4212',
            'value' => 'Mostly Cloudy and Heavy Rain',
        ],
        [
            'key' => '4201',
            'value' => 'Heavy Rain',
        ],
        [
            'key' => '5115',
            'value' => 'Mostly Clear and Flurries',
        ],
        [
            'key' => '5116',
            'value' => 'Partly Cloudy and Flurries',
        ],
        [
            'key' => '5117',
            'value' => 'Mostly Cloudy and Flurries',
        ],
        [
            'key' => '5001',
            'value' => 'Flurries',
        ],
        [
            'key' => '5100',
            'value' => 'Light Snow',
        ],
        [
            'key' => '5102',
            'value' => 'Mostly Clear and Light Snow',
        ],
        [
            'key' => '5103',
            'value' => 'Partly Cloudy and Light Snow',
        ],
        [
            'key' => '5104',
            'value' => 'Mostly Cloudy and Light Snow',
        ],
        [
            'key' => '5122',
            'value' => 'Drizzle and Light Snow',
        ],
        [
            'key' => '5105',
            'value' => 'Mostly Clear and Snow',
        ],
        [
            'key' => '5106',
            'value' => 'Partly Cloudy and Snow',
        ],
        [
            'key' => '5107',
            'value' => 'Mostly Cloudy and Snow',
        ],
        [
            'key' => '5000',
            'value' => 'Snow',
        ],
        [
            'key' => '5101',
            'value' => 'Heavy Snow',
        ],
        [
            'key' => '5119',
            'value' => 'Mostly Clear and Heavy Snow',
        ],
        [
            'key' => '5120',
            'value' => 'Partly Cloudy and Heavy Snow',
        ],
        [
            'key' => '5121',
            'value' => 'Mostly Cloudy and Heavy Snow',
        ],
        [
            'key' => '5110',
            'value' => 'Drizzle and Snow',
        ],
        [
            'key' => '5108',
            'value' => 'Rain and Snow',
        ],
        [
            'key' => '5114',
            'value' => 'Snow and Freezing Rain',
        ],
        [
            'key' => '5112',
            'value' => 'Snow and Ice Pellets',
        ],
        [
            'key' => '6000',
            'value' => 'Freezing Drizzle',
        ],
        [
            'key' => '6003',
            'value' => 'Mostly Clear and Freezing drizzle',
        ],
        [
            'key' => '6002',
            'value' => 'Partly Cloudy and Freezing drizzle',
        ],
        [
            'key' => '6004',
            'value' => 'Mostly Cloudy and Freezing drizzle',
        ],
        [
            'key' => '6204',
            'value' => 'Drizzle and Freezing Drizzle',
        ],
        [
            'key' => '6206',
            'value' => 'Light Rain and Freezing Drizzle',
        ],
        [
            'key' => '6205',
            'value' => 'Mostly Clear and Light Freezing Rain',
        ],
        [
            'key' => '6203',
            'value' => 'Partly Cloudy and Light Freezing Rain',
        ],
        [
            'key' => '6209',
            'value' => 'Mostly Cloudy and Light Freezing Rain',
        ],
        [
            'key' => '6200',
            'value' => 'Light Freezing Rain',
        ],
        [
            'key' => '6213',
            'value' => 'Mostly Clear and Freezing Rain',
        ],
        [
            'key' => '6214',
            'value' => 'Partly Cloudy and Freezing Rain',
        ],
        [
            'key' => '6215',
            'value' => 'Mostly Cloudy and Freezing Rain',
        ],
        [
            'key' => '6001',
            'value' => 'Freezing Rain',
        ],
        [
            'key' => '6212',
            'value' => 'Drizzle and Freezing Rain',
        ],
        [
            'key' => '6220',
            'value' => 'Light Rain and Freezing Rain',
        ],
        [
            'key' => '6222',
            'value' => 'Rain and Freezing Rain',
        ],
        [
            'key' => '6207',
            'value' => 'Mostly Clear and Heavy Freezing Rain',
        ],
        [
            'key' => '6202',
            'value' => 'Partly Cloudy and Heavy Freezing Rain',
        ],
        [
            'key' => '6208',
            'value' => 'Mostly Cloudy and Heavy Freezing Rain',
        ],
        [
            'key' => '6201',
            'value' => 'Heavy Freezing Rain',
        ],
        [
            'key' => '7110',
            'value' => 'Mostly Clear and Light Ice Pellets',
        ],
        [
            'key' => '7111',
            'value' => 'Partly Cloudy and Light Ice Pellets',
        ],
        [
            'key' => '7112',
            'value' => 'Mostly Cloudy and Light Ice Pellets',
        ],
        [
            'key' => '7102',
            'value' => 'Light Ice Pellets',
        ],
        [
            'key' => '7108',
            'value' => 'Mostly Clear and Ice Pellets',
        ],
        [
            'key' => '7107',
            'value' => 'Partly Cloudy and Ice Pellets',
        ],
        [
            'key' => '7109',
            'value' => 'Mostly Cloudy and Ice Pellets',
        ],
        [
            'key' => '7000',
            'value' => 'Ice Pellets',
        ],
        [
            'key' => '7105',
            'value' => 'Drizzle and Ice Pellets',
        ],
        [
            'key' => '7106',
            'value' => 'Freezing Rain and Ice Pellets',
        ],
        [
            'key' => '7115',
            'value' => 'Light Rain and Ice Pellets',
        ],
        [
            'key' => '7117',
            'value' => 'Rain and Ice Pellets',
        ],
        [
            'key' => '7103',
            'value' => 'Freezing Rain and Heavy Ice Pellets',
        ],
        [
            'key' => '7113',
            'value' => 'Mostly Clear and Heavy Ice Pellets',
        ],
        [
            'key' => '7114',
            'value' => 'Partly Cloudy and Heavy Ice Pellets',
        ],
        [
            'key' => '7116',
            'value' => 'Mostly Cloudy and Heavy Ice Pellets',
        ],
        [
            'key' => '7101',
            'value' => 'Heavy Ice Pellets',
        ],
        [
            'key' => '8001',
            'value' => 'Mostly Clear and Thunderstorm',
        ],
        [
            'key' => '8003',
            'value' => 'Partly Cloudy and Thunderstorm',
        ],
        [
            'key' => '8002',
            'value' => 'Mostly Cloudy and Thunderstorm',
        ],
        [
            'key' => '8000',
            'value' => 'Thunderstorm',
        ],
    ],
    'metric_values' => [
        'cloud_cover' => [
            'unit' => '%',
            'unit_label' => 'Percentage',
        ],
        'dew_point' => [
            'unit' => '°C',
            'unit_label' => 'Degrees Celsius',
        ],
        'humidity' => [
            'unit' => '%',
            'unit_label' => 'Percentage',
        ],
        'precipitation_probability' => [
            'unit' => '%',
            'unit_label' => 'Percentage',
        ],
        'rain_accumulation_lwe' => [
            'unit' => 'mm',
            'unit_label' => 'Millimeters',
        ],
        'rain_accumulation' => [
            'unit' => 'mm',
            'unit_label' => 'Millimeters',
        ],
        'rain_intensity' => [
            'unit' => 'mm/h',
            'unit_label' => 'Millimeters per hour',
        ],
        'temperature_apparent' => [
            'unit' => '°C',
            'unit_label' => 'Degrees Celsius',
        ],
        'temperature' => [
            'unit' => '°C',
            'unit_label' => 'Degrees Celsius',
        ],
        'uv_health_concern' => [
            'unit' => 'Index',
            'unit_label' => 'Index',
            'values' => [
                [
                    'range_min' => '0',
                    'range_max' => '2',
                    'value' => 'Low',
                ],
                [
                    'range_min' => '3',
                    'range_max' => '5',
                    'value' => 'Moderate',
                ],
                [
                    'range_min' => '6',
                    'range_max' => '7',
                    'value' => 'High',
                ],
                [
                    'range_min' => '8',
                    'range_max' => '10',
                    'value' => 'Very High',
                ],
                [
                    'range_min' => '11',
                    'range_max' => '15',
                    'value' => 'Extreme',
                ],
            ],
        ],
        'uv_index' => [
            'unit' => 'Index',
            'unit_label' => 'Index',
            'values' => [
                [
                    'range_min' => '0',
                    'range_max' => '2',
                    'value' => 'Low',
                ],
                [
                    'range_min' => '3',
                    'range_max' => '5',
                    'value' => 'Moderate',
                ],
                [
                    'range_min' => '6',
                    'range_max' => '7',
                    'value' => 'High',
                ],
                [
                    'range_min' => '8',
                    'range_max' => '10',
                    'value' => 'Very High',
                ],
                [
                    'range_min' => '11',
                    'range_max' => '15',
                    'value' => 'Extreme',
                ],
            ],
        ],
        'visibility' => [
            'unit' => 'km',
            'unit_label' => 'Kilometers',
        ],
        'wind_speed' => [
            'unit' => 'm/s',
            'unit_label' => 'Meters per second',
        ],
        'wind_gust' => [
            'unit' => 'm/s',
            'unit_label' => 'Meters per second',
        ],
        'wind_direction' => [
            'unit' => '°',
            'unit_label' => 'Degrees',
        ],
    ],
];
