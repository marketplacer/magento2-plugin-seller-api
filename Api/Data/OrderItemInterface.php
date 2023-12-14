<?php

namespace Marketplacer\SellerApi\Api\Data;

interface OrderItemInterface
{
    public const SELLER_ID = 'seller_id';
    public const SELLER_NAME = 'seller_name';
    public const SELLER_BUSINESS_NUMBER = 'seller_business_number';
    public const BASE_SHIPPING_FEE = 'base_shipping_fee';
    public const SHIPPING_FEE = 'shipping_fee';
    public const BASE_SHIPPING_TAX_AMOUNT = 'base_shipping_tax_amount';
    public const SHIPPING_TAX_AMOUNT = 'shipping_tax_amount';
}
