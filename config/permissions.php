<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions
    |--------------------------------------------------------------------------
    |
    | صلاحيات كل دور في النظام
    | user: المستخدم العادي - يحجز رحلات ويشاهد حجوزاته
    | admin: المدير - يتحكم في كل شيء
    |
    */

    'roles' => [
        'user' => [
            'label' => 'مستخدم',
            'permissions' => [
                'view_trips', 'view_locations',
                'create_booking', 'view_own_bookings', 'update_own_booking', 'cancel_own_booking',
            ],
        ],
        'admin' => [
            'label' => 'مدير',
            'permissions' => [
                'view_trips', 'create_trip', 'update_trip', 'delete_trip',
                'view_locations', 'create_location', 'update_location', 'delete_location',
                'view_bookings', 'cancel_booking', 'view_dashboard_stats',
                'view_payments', 'view_payment_stats',
                'view_vessels', 'create_vessel', 'update_vessel',
                'view_vessel_types', 'create_vessel_type', 'update_vessel_type',
            ],
        ],
    ],
];
