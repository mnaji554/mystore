<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::Confirmed => 'مؤكد',
            self::Processing => 'قيد التجهيز',
            self::Shipped => 'تم الشحن',
            self::Delivered => 'تم التسليم',
            self::Cancelled => 'ملغي',
            self::Refunded => 'مسترجع',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'sky',
            self::Processing => 'indigo',
            self::Shipped => 'violet',
            self::Delivered => 'emerald',
            self::Cancelled => 'rose',
            self::Refunded => 'slate',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Processing, self::Cancelled],
            self::Confirmed => [self::Processing, self::Shipped, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered, self::Cancelled],
            self::Delivered => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function customerCanCancel(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    /** Statuses whose stock has been returned to inventory. */
    public function releasesStock(): bool
    {
        return in_array($this, [self::Cancelled, self::Refunded], true);
    }

    /** Statuses counted as revenue. */
    public static function revenueStatuses(): array
    {
        return [
            self::Pending->value, self::Confirmed->value, self::Processing->value,
            self::Shipped->value, self::Delivered->value,
        ];
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
