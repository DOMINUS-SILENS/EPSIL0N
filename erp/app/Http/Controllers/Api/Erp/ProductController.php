<?php

namespace App\Http\Controllers\Api\Erp;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Aggregates\Articleggregate;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Article::paginate($request->get('per_page', 15));
        return response()->json($products);
    }

    public function show($id)
    {
        return response()->json(Article::findOrFail($id));
    }

    public function store(StoreProductRequest $request)
    {
        $id = (string)Str::uuid();
        Articleggregate::retrieve($id)
            ->createArticle($request->validated())
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function update(StoreProductRequest $request, $id)
    {
        Articleggregate::retrieve($id)
            ->updateArticle($request->validated())
            ->persist();

        return response()->json(['id' => $id, 'status' => 'accepted'], 202);
    }

    public function stockHistory($id)
    {
        $history = \App\Models\ArticleMovement::where('article_id', $id)->get();
        return response()->json($history);
    }
}
