<?php

namespace App\DataTables;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class TicketsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Ticket> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('id', function ($row) {
                return '#' . $row->id;
            })
             ->addColumn('department_name', function($row) {
                return $row->category->department ? $row->category->department->name : '<span class="text-muted">-</span>';
                })
           ->addColumn('action', function($row){
            $btn = '<a href="'.route('tickets.show', $row->id).'" class="btn btn-light btn-sm me-1">عرض</a>';
            if (auth()->user()->can('update', $row)) {
                $btn .= '<a href="' . route('tickets.show', $row->id) . '" class="btn btn-light-primary btn-sm">تعديل</a>';
            }

            return $btn;
        })
            ->editColumn('status', function($row){
                return '<span class="badge '.$row->status_badge_class.'">'.$row->status_label.'</span>';
            })
            ->editColumn('created_at', function($row){
                return $row->created_at ? $row->created_at->format('Y-m-d H:i') : '';
            })
            ->rawColumns(['action', 'status','created_at'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Ticket>
     */
    public function query(Ticket $model): QueryBuilder
    {
        return $model->newQuery()
            ->with('category', 'assignedUsers')
            ->where(function ($q) {
                $q->where('created_by', Auth::id())
                ->orWhereHas('assignedUsers', function ($sub) {
                    $sub->where('user_id', Auth::id());
                });
            })
           ->when(request('status') && request('status') !== 'all', function ($query) {
                return $query->where('status', request('status'));
            })->latest();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('tickets-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->addTableClass('table align-middle table-row-dashed fs-6 gy-5')
                    ->orderBy(3, 'desc')
                    ->parameters([
                        // 'searching' => false,
                        'language' => [
                            'url' => asset('assets/js/ar.json')
                        ]
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->width(50)->addClass('text-center'),
            Column::make('id')->title('رقم التذكرة')->addClass('text-center'),
            Column::make('title')->title('العنوان')->sortable(false),
            Column::make('status')->title('الحالة')->sortable(false),
            Column::make('department_name')->title('القسم')->sortable(false),
            Column::make('office_location')->title('عنوان القسم')->sortable(false),
            Column::make('created_at')->title('تاريخ الإنشاء')->addClass('text-center')->sortable(false),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->addClass('text-center')
                  ->title('عرض'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Tickets_' . date('YmdHis');
    }
}
