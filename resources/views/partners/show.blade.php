@extends('layouts.app')

@section('content')
<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">{{ $partner->partner_name }}</h4>
            <p class="text-muted mb-0">{{ $partner->company_name ?: 'Partner Details' }} · {{ $partner->email ?: '--' }}</p>
        </div>
        <div>
            @if(in_array(user()->permission('edit_partner'), ['all','added','both']))
                <a class="btn btn-outline-primary" href="{{ route('partners.edit', $partner->id) }}"><i class="fa fa-edit mr-1"></i> Edit</a>
            @endif
            <a class="btn btn-light" href="{{ route('partners.index') }}">Back</a>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form class="bg-white rounded shadow-sm p-3 mb-4" method="GET">
        <div class="d-flex flex-wrap align-items-end gap-2">
            <div><label class="small text-muted">Quick Filter</label><select name="range" class="form-control">
                <option value="">All Time</option><option value="today" @selected(request('range') === 'today')>Today</option>
                <option value="week" @selected(request('range') === 'week')>This Week</option><option value="month" @selected(request('range') === 'month')>This Month</option>
            </select></div>
            <div><label class="small text-muted">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
            <div><label class="small text-muted">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
            <button class="btn btn-primary">Apply Date Filter</button>
            <a class="btn btn-light" href="{{ route('partners.show', $partner->id) }}">Reset</a>
        </div>
    </form>

    <div class="row">
        @foreach([['Total Sales', $summary['total_sales'], 'fa-shopping-cart'], ['Total Revenue', '₹ '.number_format($summary['total_revenue'], 2), 'fa-rupee-sign'], ['Total Commission', '₹ '.number_format($summary['total_commission'], 2), 'fa-percent'], ['Commission Paid', '₹ '.number_format($summary['commission_paid'], 2), 'fa-check'], ['Commission Pending', '₹ '.number_format($summary['commission_pending'], 2), 'fa-clock']] as $card)
            <div class="col-md-6 col-xl-2 mb-3" style="flex:1">
                <div class="bg-white rounded shadow-sm p-3 h-100"><div class="text-muted small">{{ $card[0] }}</div><h4 class="mb-0 mt-2">{{ $card[1] }}</h4><i class="fa {{ $card[2] }} text-primary mt-2"></i></div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="bg-white rounded shadow-sm p-3 h-100">
                <h5>Product-wise Sales & Commission</h5>
                <div class="table-responsive"><table class="table table-hover">
                    <thead><tr><th>Product</th><th>Quantity Sold</th><th>Revenue</th><th>Commission Rate</th><th>Commission</th></tr></thead>
                    <tbody>@forelse($productSales as $sale)
                        <tr><td>{{ $sale->product_name }}</td><td>{{ number_format($sale->quantity_sold, 2) }}</td><td>₹ {{ number_format($sale->revenue, 2) }}</td><td>{{ number_format($sale->commission_rate, 2) }}{{ $sale->commission_type === 'percentage' ? '%' : '' }}</td><td>₹ {{ number_format($sale->commission, 2) }}</td></tr>
                    @empty<tr><td colspan="5" class="text-center text-muted">No sales found for this period.</td></tr>@endforelse</tbody>
                </table></div>
            </div>
        </div>
        <div class="col-xl-4 mb-4">
            <div class="bg-white rounded shadow-sm p-3 h-100">
                <h5>Partner Information</h5>
                <dl class="row mb-0"><dt class="col-5">Mobile</dt><dd class="col-7">{{ $partner->mobile ?: '--' }}</dd><dt class="col-5">Email</dt><dd class="col-7">{{ $partner->email ?: '--' }}</dd><dt class="col-5">Address</dt><dd class="col-7">{{ $partner->address ?: '--' }}</dd><dt class="col-5">Status</dt><dd class="col-7">{{ ucfirst($partner->status) }}</dd></dl>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="bg-white rounded shadow-sm p-3">
                <h5>Product Commission Configuration</h5>
                <p class="small text-muted">Fixed amount is per unit; percentage is calculated on sale amount. Existing sales keep their rate snapshot.</p>
                @foreach($products as $product)
                    @php($config = $commissionConfigs->get($product->id))
                    <form method="POST" action="{{ route('partners.commission_configs.store', $partner->id) }}" class="row align-items-end border-bottom py-2">
                        @csrf <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <div class="col-md-4"><label class="small">{{ $product->name }}</label></div>
                        <div class="col-md-3"><select name="commission_type" class="form-control form-control-sm"><option value="fixed" @selected(($config?->commission_type ?? 'fixed') === 'fixed')>₹ Fixed</option><option value="percentage" @selected($config?->commission_type === 'percentage')>% Percentage</option></select></div>
                        <div class="col-md-3"><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="commission_value" value="{{ $config?->commission_value ?? 0 }}"></div>
                        <div class="col-md-2"><button class="btn btn-sm btn-primary">Save</button></div>
                    </form>
                @endforeach
            </div>
        </div>
        <div class="col-xl-5 mb-4">
            @if($canRecordSale)
                <div class="bg-white rounded shadow-sm p-3 mb-4">
                    <h5>Record Sale</h5>
                    <form method="POST" action="{{ route('partners.sales.store', $partner->id) }}">@csrf
                        <div class="row">
                            <div class="col-md-6 mb-2"><label class="small">Product</label><select name="product_id" class="form-control"><option value="">Unspecified</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
                            <div class="col-md-6 mb-2"><label class="small">Quantity</label><input name="quantity" type="number" step="0.01" min="0.01" value="1" class="form-control" required></div>
                            <div class="col-md-6 mb-2"><label class="small">Sale Amount</label><input name="sale_amount" type="number" step="0.01" min="0" class="form-control" required></div>
                            <div class="col-md-6 mb-2"><label class="small">Sale Date</label><input name="sale_date" type="date" value="{{ now()->toDateString() }}" class="form-control" required></div>
                            <div class="col-md-6 mb-2"><label class="small">Lead ID (optional)</label><select name="lead_id" class="form-control"><option value="">None</option>@foreach($leads as $lead)<option value="{{ $lead->id }}">{{ $lead->id }} - {{ $lead->client_name }}</option>@endforeach</select></div>
                            <div class="col-md-6 mb-2"><label class="small">Client ID (optional)</label><select name="client_id" class="form-control"><option value="">None</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->id }} - {{ $client->name }}</option>@endforeach</select></div>
                            <div class="col-md-6 mb-2"><label class="small">Order ID (optional)</label><select name="order_id" class="form-control"><option value="">None</option>@foreach($orders as $order)<option value="{{ $order->id }}">{{ $order->id }} - {{ $order->order_number }}</option>@endforeach</select></div>
                            <div class="col-md-6 mb-2"><label class="small">Invoice ID (optional)</label><select name="invoice_id" class="form-control"><option value="">None</option>@foreach($invoices as $invoice)<option value="{{ $invoice->id }}">{{ $invoice->id }} - {{ $invoice->invoice_number }}</option>@endforeach</select></div>
                            <div class="col-12 mb-2"><label class="small">Notes</label><textarea name="notes" class="form-control"></textarea></div>
                        </div>
                        <button class="btn btn-primary">Save Sale & Calculate Commission</button>
                    </form>
                </div>
            @endif
            @if($canRecordPayment)
                <div class="bg-white rounded shadow-sm p-3">
                    <h5>Record Commission Payment</h5>
                    <form method="POST" action="{{ route('partners.commission_payments.store', $partner->id) }}">@csrf
                        <div class="row"><div class="col-md-6 mb-2"><label class="small">Amount</label><input name="amount" type="number" step="0.01" min="0.01" class="form-control" required></div><div class="col-md-6 mb-2"><label class="small">Payment Date</label><input name="payment_date" type="date" value="{{ now()->toDateString() }}" class="form-control" required></div><div class="col-md-6 mb-2"><label class="small">Method</label><input name="payment_method" class="form-control" placeholder="Bank, UPI, Cash"></div><div class="col-md-6 mb-2"><label class="small">Reference</label><input name="reference" class="form-control"></div><div class="col-12 mb-2"><label class="small">Notes</label><textarea name="notes" class="form-control"></textarea></div></div>
                        <button class="btn btn-success">Save Payment</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded shadow-sm p-3">
        <h5>Commission Payments</h5>
        <div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Notes</th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>{{ $payment->payment_date?->format('d-m-Y') }}</td><td>₹ {{ number_format($payment->amount, 2) }}</td><td>{{ $payment->payment_method ?: '--' }}</td><td>{{ $payment->reference ?: '--' }}</td><td>{{ $payment->notes ?: '--' }}</td></tr>@empty<tr><td colspan="5" class="text-muted text-center">No payments found.</td></tr>@endforelse</tbody></table></div>
    </div>
</div>
@endsection
