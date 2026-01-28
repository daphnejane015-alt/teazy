<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Malaysia Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains Malaysia-specific settings for the tea recommendation
    | system, including cities, weather patterns, tea preferences, and
    | cultural considerations.
    |
    */

    'country' => 'Malaysia',
    'country_code' => 'MY',
    'timezone' => 'Asia/Kuala_Lumpur',
    'currency' => 'MYR',

    'cities' => [
        'major' => [
            'Kuala Lumpur',
            'George Town',
            'Johor Bahru',
            'Ipoh',
            'Shah Alam',
            'Petaling Jaya',
            'Seremban',
            'Kuching',
            'Kota Kinabalu',
            'Malacca'
        ],
        'all' => [
            'Kuala Lumpur', 'KL', 'George Town', 'Penang', 'Johor Bahru', 'JB',
            'Ipoh', 'Shah Alam', 'Petaling Jaya', 'PJ', 'Seremban', 'Kuching',
            'Kota Kinabalu', 'KK', 'Malacca', 'Melaka', 'Alor Setar', 'Miri',
            'Klang', 'Kota Bharu', 'Kuala Terengganu', 'Sandakan', 'Sibu',
            'Taiping', 'Seberang Perai', 'Subang Jaya', 'Putrajaya', 'Cyberjaya',
            'Rawang', 'Kajang', 'Bangi', 'Senawang', 'Ampang', 'Cheras',
            'Gombak', 'Batu Pahat', 'Kulim', 'Banting', 'Sepang', 'Salak Tinggi'
        ]
    ],

    'weather_patterns' => [
        'hot_humid' => [
            'condition' => 'temperature >= 30 && humidity >= 70',
            'description' => 'Hot and humid Malaysian weather',
            'teas' => ['lemongrass', 'ginger', 'mint', 'honey lemon', 'green tea'],
            'caffeine' => ['low', 'caffeine_free'],
            'health_focus' => ['relax_calm', 'energy', 'hydration'],
            'recommendation' => 'Perfect for hot and humid Malaysian weather - cooling and refreshing teas'
        ],
        'rainy_season' => [
            'condition' => 'is_rainy || humidity >= 80',
            'description' => 'Malaysian rainy season',
            'teas' => ['ginger tea', 'lemongrass', 'pandan', 'ginseng', 'oolong'],
            'caffeine' => ['medium', 'low'],
            'health_focus' => ['relax_calm', 'digest', 'immune'],
            'recommendation' => 'Ideal for rainy season - warming and immunity-boosting teas'
        ],
        'haze' => [
            'condition' => 'description === "haze" || humidity <= 60',
            'description' => 'Hazy conditions in Malaysia',
            'teas' => ['green tea', 'honey lemon', 'ginger', 'peppermint', 'chamomile'],
            'caffeine' => ['low', 'medium'],
            'health_focus' => ['relax_calm', 'immune', 'stress'],
            'recommendation' => 'Great for hazy conditions - cleansing and soothing teas'
        ],
        'tropical' => [
            'condition' => 'temperature >= 25 && temperature <= 32',
            'description' => 'Typical tropical Malaysian climate',
            'teas' => ['jasmine', 'oolong', 'white tea', 'herbal blends'],
            'caffeine' => ['low', 'medium'],
            'health_focus' => ['energy', 'relax_calm'],
            'recommendation' => 'Perfect for tropical Malaysian climate - balanced and refreshing teas'
        ]
    ],

    'tea_preferences' => [
        'local_favorites' => [
            'Teh Tarik' => ['pulled tea', 'sweet', 'condensed milk'],
            'Teh O' => ['black tea', 'no milk', 'hot'],
            'Teh C' => ['black tea', 'evaporated milk', 'sweet'],
            'Teh O Limau' => ['black tea', 'lime', 'no milk'],
            'Barley' => ['barley', 'cooling', 'sweet'],
            'Lemongrass' => ['lemongrass', 'ginger', 'cooling'],
            'Tongkat Ali' => ['herbal', 'energy', 'bitter'],
            'Kacang Hijau' => ['mung bean', 'sweet', 'dessert']
        ],
        'seasonal_recommendations' => [
            'ramadan' => ['light teas', 'digestive aids', 'energy boosters'],
            'hari raya' => ['celebration teas', 'sweet varieties', 'sharing blends'],
            'chinese_new_year' => ['prosperity teas', 'red tea varieties', 'festive blends'],
            'deepavali' => ['spiced teas', 'golden varieties', 'aromatic blends'],
            'christmas' => ['festive blends', 'spiced varieties', 'warming teas']
        ]
    ],

    'cultural_considerations' => [
        'halal_requirements' => true,
        'multi_cultural_tea_culture' => true,
        'kopi_teh_culture' => true,
        'mamak_staple' => true,
        'chinese_traditions' => true,
        'indian_influences' => true,
        'malay_heritage' => true
    ],

    'temperature_thresholds' => [
        'hot' => 30,
        'warm' => 25,
        'comfortable' => 20,
        'cool' => 15,
        'cold' => 10
    ],

    'humidity_thresholds' => [
        'very_humid' => 80,
        'humid' => 70,
        'moderate' => 60,
        'dry' => 50
    ],

    'regional_focus' => [
        'west_malaysia' => ['Kuala Lumpur', 'Penang', 'Johor Bahru', 'Ipoh'],
        'east_malaysia' => ['Kuching', 'Kota Kinabalu', 'Miri', 'Sandakan'],
        'northern' => ['Alor Setar', 'Kangar', 'Kota Bharu'],
        'southern' => ['Johor Bahru', 'Melaka', 'Nusajaya'],
        'central' => ['Kuala Lumpur', 'Putrajaya', 'Shah Alam', 'Petaling Jaya']
    ],

    'local_tea_terms' => [
        'teh' => 'tea',
        'kopi' => 'coffee',
        'ais' => 'cold',
        'panas' => 'hot',
        'kurang manis' => 'less sweet',
        'manis' => 'sweet',
        'tarik' => 'pulled',
        'campur' => 'mixed',
        'soda' => 'with soda',
        'limau' => 'lime/lemon'
    ]
];
