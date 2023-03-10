@extends('layouts.app')

@section('content')
	<div class="d-sm-flex align-items-center justify-content-between mb-4">
		<h1 class="h3 mb-0 text-gray-800">Products</h1>
	</div>


	<div class="card">
		<form action="{{ route('filter-products') }}" method="post" class="card-header">
			@csrf
			<div class="form-row justify-content-between">
				<div class="col-md-2">
					<input type="text" name="title" placeholder="Product Title" class="form-control">
				</div>

				<div class="col-md-2">
					<select name="variant" id="" class="form-control">
						<option value="" selected>-- Select a variant --</option>

						@foreach ($product_variants as $variantId => $variants)
							<optgroup label="{{ $variants[0]->variant_title }}">
								@foreach ($variants as $variant)
									<option value="{{ $variant->id }}">{{ $variant->variant }}</option>
								@endforeach
							</optgroup>
						@endforeach
					</select>
				</div>

				<div class="col-md-3">
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text">Price Range</span>
						</div>
						<input type="text" name="price_from" aria-label="First name" placeholder="From" class="form-control">
						<input type="text" name="price_to" aria-label="Last name" placeholder="To" class="form-control">
					</div>
				</div>
				<div class="col-md-2">
					<input type="date" name="date" placeholder="Date" class="form-control">
				</div>
				<div class="col-md-1">
					<button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
				</div>
			</div>
		</form>

		<div class="card-body">
			<div class="table-response">
				<table class="table">
					<thead>
						<tr>
							<th>#</th>
							<th>Title</th>
							<th>Description</th>
							<th>Variant</th>
							<th width="100px">Action</th>
						</tr>
					</thead>

					<tbody>

						{{-- <tr>
							<td>1</td>
							<td>T-Shirt <br> Created at : 25-Aug-2020</td>
							<td>Quality product in low cost</td>
							<td>
								<dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant">

									<dt class="col-sm-3 pb-0">
										SM/ Red/ V-Nick
									</dt>
									<dd class="col-sm-9">
										<dl class="row mb-0">
											<dt class="col-sm-4 pb-0">Price : {{ number_format(200, 2) }}</dt>
											<dd class="col-sm-8 pb-0">InStock : {{ number_format(50, 2) }}</dd>
										</dl>
									</dd>
								</dl>
								<button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
							</td>
							<td>
								<div class="btn-group btn-group-sm">
									<a href="{{ route('product.edit', 1) }}" class="btn btn-success">Edit</a>
								</div>
							</td>
						</tr> --}}

						@forelse($products as $product)
							<tr>
								<td>{{ $loop->iteration }}</td>
								<td>{{ $product->title }} <br> Created at : {{ $product->created_at->format('d-M-Y') }}</td>
								<td>{{ Str::limit($product->description, $limit = 30, $end = '...') }}</td>
								<td>
									<dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant">

										<dt class="col-sm-3 pb-0">
											@forelse($product->prices as $price)
												{{ $price->p_v_one ? ucfirst($price->p_v_one->variant) : null }}
												{{ $price->p_v_two ? ' | ' . ucfirst($price->p_v_two->variant) : null }}
												{{ $price->p_v_three ? ' | ' . ucfirst($price->p_v_three->variant) : null }}
												<br>
											@empty
											@endforelse
										</dt>
										<dd class="col-sm-9">
											@forelse($product->prices as $price)
												<dl class="row mb-0">
													<dt class="col-sm-6 pb-0">Price : {{ number_format($price->price, 2) }}</dt>
													<dd class="col-sm-6 pb-0">InStock : {{ number_format($price->stock, 2) }}</dd>
												</dl>
											@empty
											@endforelse
										</dd>
									</dl>
									<button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show
										more</button>
								</td>
								<td>
									<div class="btn-group btn-group-sm">
										<a href="{{ route('product.edit', $product->id) }}" class="btn btn-success">Edit</a>
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td>No Data available</td>
							</tr>
						@endforelse

					</tbody>

				</table>
			</div>

		</div>

		<div class="card-footer">
			{{-- <div class="row justify-content-between">
				<div class="col-md-6">
					<p>Showing 1 to 10 out of 100</p>
				</div>
				<div class="col-md-2">

				</div>
			</div> --}}

			<div class="row px-3 justify-content-between">
				<div class="">
					<p>
						Showing {{ $showing > $total ? $total : $showing }}
						to
						{{ $to > $total ? $total : $to }}
						out of
						{{ $total }}
					</p>
				</div>
				<div class="">
					{{ $products->links() }}
				</div>
			</div>
		</div>
	</div>
@endsection
