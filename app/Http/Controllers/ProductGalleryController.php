<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductGalleryRequest;
use App\Models\Product;
use App\Models\ProductGallery;
use Yajra\DataTables\Facades\DataTables;

class ProductGalleryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        // show data in dataTable with ajax
        if (request()->ajax()) {
            $query = ProductGallery::where('products_id', $product->id);

            return DataTables::of($query)
            ->addColumn('action', function ($item) {
                return '
                    <form class="inline-block" action="' . route('dashboard.gallery.destroy', $item->id) . '" method="POST">
                    <button type="submit" class="border border-red-500 bg-red-500 text-white rounded-md px-2 py-1 m-2 transition duration-500 ease select-none hover:bg-red-600 focus:outline-none focus:shadow-outline">
                        Hapus
                    </button>
                        ' . method_field('delete') . csrf_field() . '
                    </form>';
            })
            ->editColumn('url', function ($item) {
                return $item->url;
            })
            ->editColumn('is_featured', function ($item) {
                return $item->is_featured ? 'Ya' : 'Tidak';
            })
            ->rawColumns(['action', 'url'])
            ->make();
        }

        return view('pages.dashboard.gallery.index', compact('product'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Product $product)
    {
        //
        return view('pages.dashboard.gallery.create', compact('product'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductGalleryRequest $request, Product $product)
    {
        //Store Gallery Image
        $files = $request->file('files');

        if ($request->hasFile('files'))
        {
            foreach ($files as $file) {
                $path = $file->store('gallery', 'public');

                ProductGallery::create([
                    'products_id' => $product->id,
                    'url' => $path,
                    'is_featured' => $request->input('is_featured', false),
                ]);
            }
        }

        return redirect()->route('dashboard.product.gallery.index', $product->id);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductGallery $gallery)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductGallery $gallery)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductGalleryRequest $request, ProductGallery $gallery)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductGallery $gallery)
    {
        $gallery->delete();

        return redirect()->route('dashboard.product.gallery.index', $gallery->product_id)
            ->with('success', 'Gallery image deleted successfully');
    }
}
