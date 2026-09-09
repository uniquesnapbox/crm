<?php

namespace App\Http\Controllers;

use App\DataTables\PartnerDataTable;
use App\Helper\Reply;
use App\Models\ClientDetails;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Partner;
use App\Models\PartnerCommissionPayment;
use App\Models\PartnerProductCommission;
use App\Models\PartnerSale;
use App\Models\Product;
use App\Models\User;
use App\Models\ModuleSetting;
use App\Services\PartnerCommissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerController extends AccountBaseController
{
    public function __construct(private PartnerCommissionService $commissionService)
    {
        parent::__construct();

        $this->pageTitle = 'Partners';
        $this->middleware(function ($request, $next) {
            abort_403(!ModuleSetting::checkModule('partners'));
            return $next($request);
        });
    }

    public function index(PartnerDataTable $dataTable)
    {
        abort_403(!$this->canView('view_partner'));
        $this->addPartnerPermission = user()->permission('add_partner');
        return $dataTable->render('partners.index', $this->data);
    }

    public function create()
    {
        abort_403(!$this->canCreate('add_partner'));
        $this->pageTitle = 'Add Partner';
        return view('partners.create', $this->formData());
    }

    public function store(Request $request)
    {
        abort_403(!$this->canCreate('add_partner'));
        $data = $request->validate($this->partnerRules());
        $data['company_id'] = company()->id;
        $data['created_by'] = user()->id;
        $data['last_updated_by'] = user()->id;
        Partner::create($data);
        return redirect()->route('partners.index')->with('success', 'Partner added successfully.');
    }

    public function show(Partner $partner)
    {
        $this->assertPartner($partner);
        abort_403(!$this->canView('view_partner'));
        abort_403(!$this->canView('view_partner_commission'));

        [$from, $to] = $this->dateRange(request('range'), request('from'), request('to'));
        $sales = PartnerSale::query()->where('partner_id', $partner->id)->where('status', 'confirmed');
        $payments = PartnerCommissionPayment::query()->where('partner_id', $partner->id);
        $this->applyDateRange($sales, 'sale_date', $from, $to);
        $this->applyDateRange($payments, 'payment_date', $from, $to);

        $this->partner = $partner;
        $this->from = $from;
        $this->to = $to;
        $this->summary = [
            'total_sales' => (float) (clone $sales)->sum('quantity'),
            'total_revenue' => (float) (clone $sales)->sum('sale_amount'),
            'total_commission' => (float) (clone $sales)->sum('commission_amount'),
            'commission_paid' => (float) (clone $payments)->sum('amount'),
        ];
        $this->summary['commission_pending'] = max(0, $this->summary['total_commission'] - $this->summary['commission_paid']);
        $this->productSales = (clone $sales)->leftJoin('products', 'products.id', '=', 'partner_sales.product_id')
            ->select('partner_sales.product_id', DB::raw('COALESCE(products.name, \'Unspecified\') as product_name'))
            ->selectRaw('SUM(partner_sales.quantity) as quantity_sold, SUM(partner_sales.sale_amount) as revenue, AVG(partner_sales.commission_rate) as commission_rate, MAX(partner_sales.commission_type) as commission_type, SUM(partner_sales.commission_amount) as commission')
            ->groupBy('partner_sales.product_id', 'products.name')
            ->orderByDesc('quantity_sold')->get();
        $this->sales = (clone $sales)->with(['product', 'lead', 'client'])->latest('sale_date')->latest('id')->get();
        $this->payments = (clone $payments)->latest('payment_date')->latest('id')->get();
        $this->products = Product::query()->select('id', 'name')->orderBy('name')->get();
        $this->leads = Lead::query()->select('id', 'client_name')->latest()->limit(100)->get();
        $this->clients = User::query()->select('users.id', 'users.name')
            ->whereHas('roles', fn ($query) => $query->where('name', 'client'))
            ->orderBy('users.name')->limit(100)->get();
        $this->orders = Order::query()->select('id', 'order_number', 'client_id')->latest()->limit(100)->get();
        $this->invoices = Invoice::query()->select('id', 'invoice_number', 'client_id')->latest()->limit(100)->get();
        $this->commissionConfigs = $partner->commissionConfigs()->with('product')->get()->keyBy('product_id');
        $this->canRecordSale = $this->canCreate('add_partner_sale');
        $this->canRecordPayment = $this->canCreate('add_partner_commission_payment');

        return view('partners.show', $this->data);
    }

    public function edit(Partner $partner)
    {
        $this->assertPartner($partner);
        abort_403(!$this->canManage('edit_partner', $partner));
        $this->pageTitle = 'Edit Partner';
        $data = $this->formData();
        $data['partner'] = $partner;
        return view('partners.edit', $data);
    }

    public function update(Request $request, Partner $partner)
    {
        $this->assertPartner($partner);
        abort_403(!$this->canManage('edit_partner', $partner));
        $data = $request->validate($this->partnerRules());
        $data['last_updated_by'] = user()->id;
        $partner->update($data);
        return redirect()->route('partners.index')->with('success', 'Partner updated successfully.');
    }

    public function destroy(Partner $partner)
    {
        $this->assertPartner($partner);
        abort_403(!$this->canManage('delete_partner', $partner));
        $partner->delete();
        return redirect()->route('partners.index')->with('success', 'Partner deleted successfully.');
    }

    public function toggleStatus(Partner $partner)
    {
        $this->assertPartner($partner);
        abort_403(!$this->canManage('edit_partner', $partner));
        $partner->update(['status' => $partner->status === 'active' ? 'inactive' : 'active', 'last_updated_by' => user()->id]);
        return back()->with('success', 'Partner status updated.');
    }

    public function storeSale(Request $request, Partner $partner)
    {
        $this->assertPartner($partner);
        abort_403(!$this->canCreate('add_partner_sale'));
        $data = $request->validate([
            'lead_id' => 'nullable|integer',
            'client_id' => 'nullable|integer',
            'order_id' => 'nullable|integer',
            'invoice_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
            'quantity' => 'required|numeric|min:0.01',
            'sale_amount' => 'required|numeric|min:0',
            'sale_date' => 'required|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $product = !empty($data['product_id']) ? Product::findOrFail($data['product_id']) : null;
        $this->assertLinkedRecords($data);
        $config = $this->commissionService->resolve($partner, $product);
        $data['company_id'] = company()->id;
        $data['partner_id'] = $partner->id;
        $data['commission_type'] = $config['commission_type'];
        $data['commission_rate'] = $config['commission_rate'];
        $data['commission_amount'] = $this->commissionService->calculate($config['commission_type'], $config['commission_rate'], (float) $data['sale_amount'], (float) $data['quantity']);
        $data['created_by'] = user()->id;
        PartnerSale::create($data);
        return back()->with('success', 'Partner sale recorded and commission calculated.');
    }

    public function storePayment(Request $request, Partner $partner)
    {
        $this->assertPartner($partner);
        abort_403(!$this->canCreate('add_partner_commission_payment'));
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:191',
            'notes' => 'nullable|string|max:5000',
        ]);

        $earned = (float) $partner->sales()->where('status', 'confirmed')->sum('commission_amount');
        $paid = (float) $partner->payments()->sum('amount');
        abort_if((float) $data['amount'] > max(0, $earned - $paid), 422, 'Payment cannot exceed pending commission.');
        PartnerCommissionPayment::create($data + ['company_id' => company()->id, 'partner_id' => $partner->id, 'created_by' => user()->id]);
        return back()->with('success', 'Commission payment recorded.');
    }

    public function saveCommission(Request $request, Partner $partner)
    {
        $this->assertPartner($partner);
        abort_403(!$this->canManage('edit_partner', $partner));
        $data = $request->validate([
            'product_id' => 'required|integer',
            'commission_type' => 'required|in:fixed,percentage',
            'commission_value' => 'required|numeric|min:0',
        ]);
        $product = Product::findOrFail($data['product_id']);
        PartnerProductCommission::updateOrCreate(
            ['partner_id' => $partner->id, 'product_id' => $product->id],
            ['company_id' => company()->id, 'commission_type' => $data['commission_type'], 'commission_value' => $data['commission_value'], 'is_active' => true]
        );
        return back()->with('success', 'Product commission configuration saved.');
    }

    public function reports(Request $request)
    {
        abort_403(!$this->canView('view_partner_report'));
        [$from, $to] = $this->dateRange($request->range, $request->from, $request->to);
        $sales = PartnerSale::query()->where('status', 'confirmed');
        $this->applyDateRange($sales, 'sale_date', $from, $to);
        $this->partnerRows = (clone $sales)->join('partners', 'partners.id', '=', 'partner_sales.partner_id')->select('partners.partner_name', DB::raw('SUM(partner_sales.quantity) as quantity'), DB::raw('SUM(partner_sales.sale_amount) as revenue'), DB::raw('SUM(partner_sales.commission_amount) as commission'))->groupBy('partners.id', 'partners.partner_name')->orderByDesc('revenue')->get();
        $this->productRows = (clone $sales)->leftJoin('products', 'products.id', '=', 'partner_sales.product_id')->select(DB::raw("COALESCE(products.name, 'Unspecified') as product_name"), DB::raw('SUM(partner_sales.quantity) as quantity'), DB::raw('SUM(partner_sales.sale_amount) as revenue'), DB::raw('SUM(partner_sales.commission_amount) as commission'))->groupBy('partner_sales.product_id', 'products.name')->orderByDesc('revenue')->get();
        $this->dateRows = (clone $sales)->select('sale_date', DB::raw('SUM(commission_amount) as commission'))->groupBy('sale_date')->orderByDesc('sale_date')->get();
        $payments = PartnerCommissionPayment::query();
        $this->applyDateRange($payments, 'payment_date', $from, $to);
        $this->paidRows = (clone $payments)->select('partner_id', DB::raw('SUM(amount) as paid'))->groupBy('partner_id')->pluck('paid', 'partner_id');
        $this->earnedRows = (clone $sales)->select('partner_id', DB::raw('SUM(commission_amount) as earned'))->groupBy('partner_id')->pluck('earned', 'partner_id');
        $this->partners = Partner::query()->orderBy('partner_name')->get();
        $this->from = $from;
        $this->to = $to;
        return view('partners.reports', $this->data);
    }

    private function formData(): array
    {
        return [
            'products' => Product::query()->select('id', 'name')->orderBy('name')->get(),
        ];
    }

    private function partnerRules(): array
    {
        return [
            'partner_name' => 'required|string|max:191',
            'company_name' => 'nullable|string|max:191',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
            'status' => 'required|in:active,inactive',
        ];
    }

    private function assertPartner(Partner $partner): void
    {
        abort_403((int) $partner->company_id !== (int) company()->id);
    }

    private function canView(string $permission): bool
    {
        return in_array('admin', user_roles(), true)
            || in_array(user()->permission($permission), ['all', 'added', 'owned', 'both'], true);
    }

    private function canCreate(string $permission): bool
    {
        return in_array('admin', user_roles(), true)
            || in_array(user()->permission($permission), ['all', 'added', 'both'], true);
    }

    private function canManage(string $permission, Partner $partner): bool
    {
        $value = user()->permission($permission);
        return in_array('admin', user_roles(), true)
            || $value === 'all' || ($value === 'added' && (int) $partner->created_by === (int) user()->id)
            || ($value === 'both' && (int) $partner->created_by === (int) user()->id);
    }

    private function dateRange(?string $range, ?string $from, ?string $to): array
    {
        $today = Carbon::now(company()->timezone)->toDateString();
        if ($range === 'today') return [$today, $today];
        if ($range === 'week') return [Carbon::now(company()->timezone)->startOfWeek()->toDateString(), $today];
        if ($range === 'month') return [Carbon::now(company()->timezone)->startOfMonth()->toDateString(), $today];
        return [$from ?: null, $to ?: null];
    }

    private function applyDateRange($query, string $column, ?string $from, ?string $to): void
    {
        if ($from) $query->whereDate($column, '>=', $from);
        if ($to) $query->whereDate($column, '<=', $to);
    }

    private function assertLinkedRecords(array $data): void
    {
        if (!empty($data['lead_id'])) {
            abort_unless(Lead::where('company_id', company()->id)->whereKey($data['lead_id'])->exists(), 422, 'Selected lead is not part of this company.');
        }
        if (!empty($data['client_id'])) {
            abort_unless(User::where('company_id', company()->id)->whereKey($data['client_id'])->exists(), 422, 'Selected client is not part of this company.');
        }
        if (!empty($data['order_id'])) {
            abort_unless(Order::where('company_id', company()->id)->whereKey($data['order_id'])->exists(), 422, 'Selected order is not part of this company.');
        }
        if (!empty($data['invoice_id'])) {
            abort_unless(Invoice::where('company_id', company()->id)->whereKey($data['invoice_id'])->exists(), 422, 'Selected invoice is not part of this company.');
        }
    }
}
