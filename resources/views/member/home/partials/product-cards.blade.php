@foreach ($products as $index => $product)
    <x-member.product-card
        :product="$product"
        :image-eager="($imageOffset ?? 0) + $index < 4"
    />
@endforeach
