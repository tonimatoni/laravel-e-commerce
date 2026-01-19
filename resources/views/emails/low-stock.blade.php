@component('mail::message')
# Low Stock Alert

The following product is running low on stock:

**Product:** {{ $product->name }}  
**Current Stock:** {{ $product->stock_quantity }}  
**Threshold:** {{ $threshold }}

Please restock this product soon.

@component('mail::button', ['url' => config('app.url') . '/products'])
View Products
@endcomponent

Thanks,  
{{ config('app.name') }}
@endcomponent
