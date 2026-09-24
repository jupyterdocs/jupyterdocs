<?php

namespace App\Http\Controllers;

use App\Support\Search\SearchSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function __invoke(Request $request, SearchSuggester $suggester): JsonResponse
    {
        return response()->json([
            'suggestions' => $suggester->suggest((string) $request->query('q', '')),
        ]);
    }
}
