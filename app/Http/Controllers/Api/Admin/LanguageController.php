<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->paginateQuery(
            Language::query(),
            $request,
            searchColumns: ['code', 'name'],
            filterable: ['is_active'],
            sortable: ['code', 'name'],
        );

        return LanguageResource::collection($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'name' => ['required', 'string'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if (! empty($data['is_default'])) {
            Language::query()->update(['is_default' => false]);
        }

        $language = Language::query()->create($data);

        return response()->json(['data' => new LanguageResource($language)], 201);
    }

    public function update(Request $request, Language $language): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if (! empty($data['is_default'])) {
            Language::query()->where('id', '!=', $language->id)->update(['is_default' => false]);
        }

        $language->update($data);

        return response()->json(['data' => new LanguageResource($language->fresh())]);
    }

    public function destroy(Language $language): JsonResponse
    {
        abort_if($language->is_default, 422, 'Cannot delete default language.');
        $language->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
