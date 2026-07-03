<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Collection;

class ShopIndustryRegistry
{
    /** @var array<string, array{id: string, name: string, rate: int, categories: list<array{id: int, name: string, slug: string}>}>|null */
    private ?array $resolved = null;

    /** @return array<string, array{id: string, name: string, rate: int, categories: list<array{id: int, name: string, slug: string}>}> */
    public function industries(): array
    {
        return $this->resolveIndustries();
    }

    public function industry(string $id): ?array
    {
        return $this->industries()[$id] ?? null;
    }

    /** @return list<int> */
    public function allowedCategoryIds(?string $industryId, ?array $businessCategoryIds): array
    {
        if ($industryId === null || $industryId === '') {
            return $this->allActiveCategoryIds();
        }

        $industry = $this->industry($industryId);

        if ($industry === null) {
            return [];
        }

        $available = collect($industry['categories'])->pluck('id')->all();

        if ($businessCategoryIds === null || $businessCategoryIds === []) {
            return $available;
        }

        return array_values(array_intersect(
            array_map('intval', $businessCategoryIds),
            $available,
        ));
    }

    public function shopAllowsProduct(Shop $shop, Product $product): bool
    {
        $categoryId = (int) $product->category_id;

        if ($categoryId <= 0) {
            return false;
        }

        return in_array(
            $categoryId,
            $this->allowedCategoryIds($shop->industry_id, $shop->business_category_ids),
            true,
        );
    }

    /** @param  list<int|string>  $businessCategoryIds */
    public function validateSelection(string $industryId, array $businessCategoryIds): bool
    {
        $industry = $this->industry($industryId);

        if ($industry === null) {
            return false;
        }

        $selected = array_values(array_unique(array_map('intval', $businessCategoryIds)));

        if ($selected === []) {
            return false;
        }

        $available = collect($industry['categories'])->pluck('id')->all();

        return count(array_diff($selected, $available)) === 0;
    }

    /** @return list<int> */
    private function allActiveCategoryIds(): array
    {
        return Category::query()
            ->where('status', Category::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return array<string, array{id: string, name: string, rate: int, categories: list<array{id: int, name: string, slug: string}>}> */
    private function resolveIndustries(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        /** @var Collection<string, Category> $categoriesBySlug */
        $categoriesBySlug = Category::query()
            ->where('status', Category::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug'])
            ->keyBy('slug');

        $allCategories = $categoriesBySlug
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->values()
            ->all();

        $resolved = [];

        foreach (config('shop_industries', []) as $id => $definition) {
            $slugs = $definition['categories'] ?? [];

            $categories = $slugs === '*'
                ? $allCategories
                : collect($slugs)
                    ->map(fn (string $slug) => $categoriesBySlug->get($slug))
                    ->filter()
                    ->map(fn (Category $category): array => [
                        'id' => (int) $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ])
                    ->values()
                    ->all();

            $resolved[$id] = [
                'id' => (string) $id,
                'name' => (string) ($definition['name'] ?? $id),
                'rate' => (int) ($definition['rate'] ?? 0),
                'categories' => $categories,
            ];
        }

        return $this->resolved = $resolved;
    }
}
