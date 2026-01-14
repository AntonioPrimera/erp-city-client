<?php

namespace ERPClient\Enums;

enum PaymentType: string
{
    case Card = 'card';
    case OnDelivery = 'on_delivery';
}
