@foreach ($products as $product)
    <x-member.product-list-item :product="$product" />
@endforeach
