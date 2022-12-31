<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Exception;
use Illuminate\Http\Request;
use Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        // return view('products.index');

        $data['products'] = Product::with(['prices'])->paginate(2);

        $data['showing'] = $data['products']->perPage() * ($data['products']->currentPage() - 1) + 1;
        $data['to'] = $data['products']->perPage() * $data['products']->currentPage();
        $data['total'] = $data['products']->total();

        // $data['product_variants'] = ProductVariant::all();
        $data['product_variants'] = ProductVariant::join('variants', 'product_variants.variant_id', '=', 'variants.id')
            ->select('product_variants.*', 'variants.title as variant_title')
            ->get()
            ->groupBy('variant_id');

        return view('products.index', $data);
    }

    public function filter(Request $request)
    {
        // dd($request->all());
        $title = $request->title;
        $variant = $request->variant;
        $price_from = $request->price_from;
        $price_to = $request->price_to;
        $date = $request->date;

        $variant_price = [$price_from, $price_to, $variant];

        // $data['product_variants'] = ProductVariant::all();
        $data['product_variants'] = ProductVariant::join('variants', 'product_variants.variant_id', '=', 'variants.id')
            ->select('product_variants.*', 'variants.title as variant_title')
            ->get()
            ->groupBy('variant_id');

        try {
            $data['products'] = Product::with('prices')
                ->when($title, function ($query, $title) {
                    return $query->where('title', 'like', '%' . $title . '%');
                })
                ->when($date, function ($query, $date) {
                    return $query->whereDate('created_at', $date);
                })->whereHas('prices', function ($q) use ($variant_price) {

                    $price_from = $variant_price[0];
                    $price_to = $variant_price[1];
                    $variant = $variant_price[2];

                    $q->when($price_from, function ($query, $price_from) {
                        return $query->where('price', '>=', intval($price_from));
                    })->when($price_to, function ($query, $price_to) {
                        return $query->where('price', '<=', intval($price_to));
                    })->when($variant, function ($query, $variant) {
                        return $query->whereRaw("(product_variant_one = $variant or product_variant_two = $variant or product_variant_three = $variant)");
                    });
                })->paginate(2);

            $data['products']->appends($request->all());

            $data['showing'] = $data['products']->perPage() * ($data['products']->currentPage() - 1) + 1;
            $data['to'] = $data['products']->perPage() * $data['products']->currentPage();
            $data['total'] = $data['products']->total();

            return view('products.index', $data);
        } catch (Exception $e) {

            return $e->getMessage();
        }

        return abort(500);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // return $request->all();

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'title' => 'required|max:255',
                'sku' => 'required|unique:products|max:255',
                'description' => 'sometimes|string',
                'product_image' => 'required|array|min:1',
                'product_image.*' => 'required|file',
                'product_variant' => 'sometimes|array|min:1',
                'product_variant_prices' => 'required_if:product_variant,present|array|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Create a new product
            // return response()->json(['success' => ['message' => $request->all()]], 201);
            $product = Product::create([
                'title' => $request->title,
                'sku' => $request->sku,
                'description' => $request->description,
            ]);

            // Save the product images to the database
            $productImages = [];
            foreach ($request->product_image as $image) {
                $filename = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/products'), $filename);
                $productImages[] = new ProductImage([
                    'product_id' => $product->id,
                    'file_path' => $filename,
                ]);
            }
            $product->productImages()->saveMany($productImages);

            $product_variant = new ProductVariant();
            foreach ($request->product_variant as $variant) {
                $variant = json_decode($variant);
                foreach ($variant->tags as $tag) {
                    $product_variant->create([
                        'variant' => $tag, 'variant_id' => $variant->option, 'product_id' => $product->id
                    ]);
                }
            }

            // Save the product variant prices to the database
            foreach ($request->product_variant_prices as $price) {
                $price = json_decode($price);
                $attrs = explode("/", $price->title);

                $productVariantIds = [];
                foreach ($attrs as $attr) {
                    $productVariant = ProductVariant::where('variant', $attr)
                        ->latest()
                        ->first();

                    if ($productVariant) {
                        $productVariantIds[] = $productVariant->id;
                    }
                }

                $product->prices()->create([
                    'product_variant_one' => $productVariantIds[0],
                    'product_variant_two' => $productVariantIds[1] ?? null,
                    'product_variant_three' => $productVariantIds[2] ?? null,
                    'price' => $price->price,
                    'stock' => $price->stock,
                ]);
            }

            return response()->json(['message' => 'Product added successfully'], 200);
        } catch (Exception $e) {

            return response($e, 500);
        }

        return response()
            ->json([
                'code'      =>  500,
                'message'   =>  'Something Went Wrong!'
            ], 500);
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
