<?php

namespace ERPClient\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'În așteptare',
            self::Processing => 'În procesare',
            self::Completed => 'Finalizată',
            self::Cancelled => 'Anulată',
        };
    }
}
