@component('mail::message')
# Daily Sales Report

**Date:** {{ $date }}  
**Total Items Sold:** {{ $total_items_sold }}  
**Total Revenue:** ${{ number_format($total_revenue, 2) }}

@if(count($products) > 0)
## Top Products

@component('mail::table')
| Product | Quantity Sold | Revenue |
|:--------|:-------------:|--------:|
@foreach($products as $product)
| {{ $product['product_name'] }} | {{ $product['total_units_sold'] }} | ${{ number_format($product['revenue'], 2) }} |
@endforeach
@endcomponent
@else
No products sold on this date.
@endif

Thanks,  
{{ config('app.name') }}
@endcomponent
