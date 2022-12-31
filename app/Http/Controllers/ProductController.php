<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\ProductCreateRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
    public function store(ProductCreateRequest $request)
    {
        try {
            // Create a new product
            // return response()->json(['success' => ['message' => $request->all()]], 201);
            $product = Product::create([
                'title' => $request->title,
                'sku' => $request->sku,
                'description' => $request->description,
            ]);

            // Delete the existing product images, if any
            // Save the new product images to the database, if any are provided
            self::handleProductImages($request, $product);

            // Delete the existing product variants, if any
            // Save the new product variants to the database, if any are provided
            self::handlePproductVariants($request, $product);

            // // Delete the existing product variant prices, if any
            // // Save the new product variant prices to the database, if any are provided
            self::handleProductVariantPrices($request, $product);

            return response()->json(['message' => 'Product added successfully'], 200);
        } catch (\Exception $e) {

            return $e->getMessage();
        }

        return response()->json(['message' => 'Something Went Wrong!'], 500);
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
        $data['product'] = Product::with(['prices', 'product_variants'])->find($product->id);
        $data['variants'] = Variant::all();
        return view('products.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        try {
            // Update the product attributes
            $product->title = $request->title;
            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->save();

            // Delete the existing product images, if any
            // Save the new product images to the database, if any are provided
            self::handleProductImages($request, $product);

            // Delete the existing product variants, if any
            // Save the new product variants to the database, if any are provided
            self::handlePproductVariants($request, $product);

            // // Delete the existing product variant prices, if any
            // // Save the new product variant prices to the database, if any are provided
            self::handleProductVariantPrices($request, $product);

            return response()->json(['message' => 'Product updated successfully'], 200);
        } catch (\Exception $e) {

            return $e->getMessage();
        }

        return response()->json(['message' => 'Something Went Wrong!'], 500);
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

    public function handleProductImages($request, $product)
    {
        // Delete the existing product images, if any
        $product->productImages()->delete();

        // Save the new product images to the database, if any are provided
        if ($request->has('product_image')) {
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
        }
    }

    public function handlePproductVariants($request, $product)
    {
        // Delete the existing product variants, if any
        $product->product_variants()->delete();

        // Save the new product variants to the database, if any are provided
        if ($request->has('product_variant')) {
            $product_variant = new ProductVariant();
            foreach ($request->product_variant as $variant) {
                $variant = json_decode($variant);
                foreach ($variant->tags as $tag) {
                    $product_variant->create([
                        'variant' => $tag, 'variant_id' => $variant->option, 'product_id' => $product->id
                    ]);
                }
            }
        }
    }

    public function handleProductVariantPrices($request, $product)
    {
        // Delete the existing product variant prices, if any
        $product->prices()->delete();

        // Save the new product variant prices to the database, if any are provided
        if ($request->has('product_variant_prices')) {
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
        }
    }
}
