<?php

namespace App\Enums;

enum Role: string
{
    case User = 'user';
    case Admin = 'admin';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::User => 'مستخدم',
            self::Admin => 'مدير',
            self::Support => 'دعم فني',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::User => [
                'view_trips',
                'view_locations',
                'create_booking',
                'view_own_bookings',
                'update_own_booking',
                'cancel_own_booking',
                'create_support_ticket',
                'view_own_tickets',
                'send_chat_message',
            ],
            self::Support => [
                'view_trips', 'view_locations',
                'view_bookings', 'view_support_tickets', 'respond_to_tickets',
                'view_users', 'view_reviews',
                'send_chat_message', 'view_all_chats',
            ],
            self::Admin => [
                'view_trips', 'create_trip', 'update_trip', 'delete_trip',
                'view_locations', 'create_location', 'update_location', 'delete_location',
                'view_bookings', 'cancel_booking',
                'view_payments', 'view_payment_stats',
                'view_vessels', 'create_vessel', 'update_vessel',
                'view_vessel_types', 'create_vessel_type', 'update_vessel_type',
                'view_dashboard_stats',
                'view_users', 'create_user', 'update_user', 'delete_user',
                'view_support_tickets', 'respond_to_tickets',
                'view_reviews', 'delete_review',
                'manage_settings', 'manage_policies',
                'view_login_logs', 'send_chat_message', 'view_all_chats',
            ],
        };
    }

    public function has(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
