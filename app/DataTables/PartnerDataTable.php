<?php

namespace App\DataTables;

use App\Helper\Common;
use App\Models\Partner;
use App\Models\PartnerCommissionPayment;
use App\Models\PartnerSale;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class PartnerDataTable extends BaseDataTable
{
    private string|false $viewPermission;
    private string|false $editPermission;
    private string|false $deletePermission;

    public function __construct()
    {
        parent::__construct();
        $this->viewPermission = user()->permission('view_partner');
        $this->editPermission = user()->permission('edit_partner');
        $this->deletePermission = user()->permission('delete_partner');
    }

    public function dataTable($query)
    {
        return datatables()->eloquent($query)
            ->addIndexColumn()
            ->editColumn('status', fn ($row) => $row->status === 'active' ? Common::active() : Common::inactive())
            ->editColumn('total_sales', fn ($row) => number_format((float) $row->total_sales, 2))
            ->editColumn('total_commission', fn ($row) => number_format((float) $row->total_commission, 2))
            ->editColumn('paid_commission', fn ($row) => number_format((float) $row->paid_commission, 2))
            ->editColumn('pending_commission', fn ($row) => number_format(max(0, (float) $row->total_commission - (float) $row->paid_commission), 2))
            ->addColumn('action', function ($row) {
                $html = '<div class="d-flex justify-content-end gap-1 partner-actions">';
                $html .= '<a class="btn btn-sm btn-outline-secondary" title="View" href="' . route('partners.show', $row->id) . '"><i class="fa fa-eye"></i></a>';

                if ($this->canManage($this->editPermission, $row)) {
                    $html .= '<a class="btn btn-sm btn-outline-primary" title="Edit" href="' . route('partners.edit', $row->id) . '"><i class="fa fa-edit"></i></a>';
                }

                if ($this->canManage($this->deletePermission, $row)) {
                    $html .= '<form method="POST" action="' . route('partners.destroy', $row->id) . '" class="d-inline partner-delete-form">'
                        . csrf_field() . method_field('DELETE')
                        . '<button class="btn btn-sm btn-outline-danger" title="Delete" type="submit" onclick="return confirm(\'Delete this partner?\')"><i class="fa fa-trash"></i></button></form>';
                }

                $toggleLabel = $row->status === 'active' ? 'Deactivate' : 'Activate';
                $toggleClass = $row->status === 'active' ? 'outline-warning' : 'outline-success';
                if ($this->canManage($this->editPermission, $row)) {
                    $html .= '<form method="POST" action="' . route('partners.toggle_status', $row->id) . '" class="d-inline">'
                        . csrf_field() . '<button class="btn btn-sm btn-' . $toggleClass . '" title="' . $toggleLabel . '" type="submit"><i class="fa fa-power-off"></i></button></form>';
                }

                return $html . '</div>';
            })
            ->rawColumns(['status', 'action']);
    }

    public function query(Partner $model): Builder
    {
        $query = $model->newQuery()
            ->select('partners.*')
            ->selectSub(PartnerSale::query()->selectRaw('COALESCE(SUM(quantity), 0)')
                ->whereColumn('partner_sales.partner_id', 'partners.id')->where('status', 'confirmed'), 'total_sales')
            ->selectSub(PartnerSale::query()->selectRaw('COALESCE(SUM(commission_amount), 0)')
                ->whereColumn('partner_sales.partner_id', 'partners.id')->where('status', 'confirmed'), 'total_commission')
            ->selectSub(PartnerCommissionPayment::query()->selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('partner_commission_payments.partner_id', 'partners.id'), 'paid_commission');

        if (in_array($this->viewPermission, ['added', 'both'], true)) {
            $query->where('partners.created_by', user()->id);
        }

        if (request('status') && request('status') !== 'all') {
            $query->where('partners.status', request('status'));
        }

        $search = $this->request()->input('searchText') ?: $this->request()->input('search.value');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('partner_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function html()
    {
        $table = $this->setBuilder('partners-table', 1)->parameters([
            'initComplete' => 'function () { window.LaravelDataTables["partners-table"].buttons().container().appendTo("#table-actions") }',
            'responsive' => true,
        ]);

        if (canDataTableExport()) {
            $table->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> Export']));
        }

        return $table;
    }

    protected function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex', '#')->orderable(false)->searchable(false),
            Column::make('partner_name')->title('Partner Name'),
            Column::make('company_name')->title('Company/Agency'),
            Column::make('mobile')->title('Mobile'),
            Column::make('email')->title('Email'),
            Column::make('status')->title('Status'),
            Column::make('total_sales')->title('Total Sales'),
            Column::make('total_commission')->title('Total Commission'),
            Column::make('paid_commission')->title('Paid Commission'),
            Column::computed('pending_commission')->title('Pending Commission')->orderable(false)->searchable(false),
            Column::computed('action', 'Actions')->exportable(false)->printable(false)->orderable(false)->searchable(false)->addClass('text-right'),
        ];
    }

    private function canManage(string|false $permission, Partner $partner): bool
    {
        return $permission === 'all' || ($permission === 'added' && (int) $partner->created_by === (int) user()->id)
            || ($permission === 'both' && (int) $partner->created_by === (int) user()->id);
    }
}
