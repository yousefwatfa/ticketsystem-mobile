<?php

namespace App\DataTables;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ViewerTicketsTable extends DataTable
{
public function dataTable(QueryBuilder $query): EloquentDataTable
{
    $baseFilterQuery = Ticket::query()
        ->when(request()->filled('department_id') && request('department_id') !== 'all', function ($q) {
            $q->whereHas('category', function($catQ) {
                $catQ->where('department_id', request('department_id'));
            });
        })
        ->when(request()->filled('from_date'), function ($q) {
            $q->whereDate('created_at', '>=', request('from_date'));
        })
        ->when(request()->filled('to_date'), function ($q) {
            $q->whereDate('created_at', '<=', request('to_date'));
        });

    return (new EloquentDataTable($query))
        ->addIndexColumn()
        ->editColumn('id', function ($row) {
            return '#' . $row->id;
        })
        ->addColumn('title_formatted', function($row) {
            $title = '<div class="fw-bold mb-1">'.$row->title.'</div>';
            if($row->office_location) {
                $title .= '<small class="text-muted"><i class="bi bi-geo-alt me-1"></i>'.$row->office_location.'</small>';
            }
            return $title;
        })
        ->addColumn('creator_fullname', function($row) {
            if(!$row->creator) {
                return '<span class="text-muted">-</span>';
            }
            
            $html = '<div class="d-flex align-items-center">';
            $html .= '<div class="d-flex flex-column">';
            $html .= '<a href="#" class="text-dark text-hover-primary fw-bold mb-1">'.$row->creator->fullname.'</a>';
            
            if ($row->creator_id !== $row->created_by) {
                $creator = \App\Models\User::find($row->created_by);
                if($creator) {
                    $html .= '<span class="badge badge-light-warning badge-sm">نيابة عن '.$creator->fullname.'</span>';
                }
            }
            
            $html .= '</div></div>';
            return $html;
        })
        ->addColumn('category_name', function($row) {
            return $row->category ? '<span class="badge badge-light-info">'.$row->category->name.'</span>' : '<span class="text-muted">-</span>';
        })
        ->addColumn('department_name', function($row) {
            if($row->category && $row->category->department) {
                return '<span class="badge badge-light-primary">'.$row->category->department->name.'</span>';
            }
            return '<span class="text-muted">-</span>';
        })
        ->addColumn('assigned_to', function($row){
            $users = $row->assignedUsers->unique('id');
            if ($users->isEmpty()) {
                return '<span class="badge badge-light">غير معين</span>';
            }
            $html = '<div class="d-flex flex-wrap gap-1">';
            foreach($users as $user) {
                $html .= '<span class="badge badge-light-success">' . $user->fullname . '</span>';
            }
            $html .= '</div>';
            return $html;
        })
        ->editColumn('status', function($row){
            $badgeClass = match($row->status) {
                'pending' => 'badge-warning',
                'open' => 'badge-info',
                'in_progress' => 'badge-primary',
                'resolved' => 'badge-success',
                'closed' => 'badge-secondary',
                'cancelled' => 'badge-danger',
                'finished' => 'badge-success',
                default => 'badge-light'
            };
            return '<span class="badge '.$badgeClass.' fs-7 fw-bold">'.$row->status_label.'</span>';
        })
        ->addColumn('action', function($row){
            return '<a href="'.route('tickets.show', $row->id).'" class="btn btn-sm btn-light-primary" title="عرض التذكرة"><i class="bi bi-eye me-1"></i>عرض</a>';
        })
        ->addColumn('created_date', function($row){
            return $row->created_at ? '<div class="text-nowrap"><i class="bi bi-calendar3 text-muted me-1"></i>'.$row->created_at->format('Y-m-d').'</div>' : '-';
        })
        ->addColumn('created_time', function($row){
            return $row->created_at ? '<div class="text-nowrap text-muted"><i class="bi bi-clock text-muted me-1"></i>'.$row->created_at->format('H:i').'</div>' : '-';
        })
        ->filterColumn('id', function ($query, $keyword) {
            $normalizedKeyword = trim(str_replace('#', '', $keyword));
            if (is_numeric($normalizedKeyword)) {
                $query->where('id', (int) $normalizedKeyword);
            }
        })
        ->with('updatedCounts', [
            'all'         => (clone $baseFilterQuery)->count(),
            'open'        => (clone $baseFilterQuery)->where('status', 'open')->count(),
            'in_progress' => (clone $baseFilterQuery)->where('status', 'in_progress')->count(),
            'pending'     => (clone $baseFilterQuery)->where('status', 'pending')->count(),
            'finished'    => (clone $baseFilterQuery)->where('status', 'finished')->count(),
            'closed'      => (clone $baseFilterQuery)->where('status', 'closed')->count(),
        ])
        ->rawColumns(['status', 'assigned_to', 'action', 'creator_fullname', 'category_name', 'department_name', 'title_formatted', 'created_date', 'created_time'])
        ->setRowId('id');
}

public function query(Ticket $model): QueryBuilder
{
    return $model->newQuery()
        ->when(request()->filled('department_id') && request('department_id') !== 'all', function ($query) {
            $query->whereHas('category', function($q) {
                $q->where('department_id', request('department_id'));
            });
        })
        ->when(request()->filled('from_date'), function ($query) {
            $query->whereDate('created_at', '>=', request('from_date'));
        })
        ->when(request()->filled('to_date'), function ($query) {
            $query->whereDate('created_at', '<=', request('to_date'));
        })
        ->when(request()->filled('status') && request('status') !== 'all', function ($query) {
            $query->where('status', request('status'));
        })
        ->with(['category', 'creator', 'assignedUsers'])
        ->latest();
}

public function html(): HtmlBuilder
{
    return $this->builder()
        ->setTableId('viewer-tickets-table')
        ->columns($this->getColumns())
        ->minifiedAjax('', "
            data.status = $('.filter-btn.active').data('status');
            data.department_id = $('#department_filter').val();
            data.from_date = $('#from_date').val();
            data.to_date = $('#to_date').val();
        ")
        ->parameters([
            'language' => ['url' => asset('assets/js/ar.json')],
           'drawCallback' => "function(settings) {
            var api = this.api();
            var json = api.ajax.json();

            if (json && json.updatedCounts) {
                var counts = json.updatedCounts;

                $('#all-count').text(counts.all);
                $('#count-open').text(counts.open);
                $('#count-in_progress').text(counts.in_progress);
                $('#count-pending').text(counts.pending);
                $('#count-finished').text(counts.finished);
                $('#count-closed').text(counts.closed);
            }
        }",
        'initComplete' => "function() {
                        var table = window.LaravelDataTables['viewer-tickets-table'];
                        $('#department_filter, #from_date, #to_date').on('change', function() {
                            table.draw();
                        });
                        $('.filter-btn').on('click', function() {
                            table.draw();
                        });
                    }",
        ]);
}

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->width(50)->addClass('text-center'),
            Column::make('id')->title('رقم التذكرة')->addClass('text-center')->sortable(false),
            Column::computed('title_formatted')->title('العنوان')->sortable(false),
            Column::computed('creator_fullname')->title('المستخدم')->sortable(false),
            Column::computed('department_name')->title('القسم')->addClass('text-center')->sortable(false),
            Column::computed('category_name')->title('المشكلة')->addClass('text-center')->sortable(false),
            Column::make('status')->title('الحالة')->addClass('text-center'),
            Column::computed('assigned_to')->title('تم تعيينه لـ')->addClass('text-center')->sortable(false),
            Column::computed('created_date')->title('التاريخ')->addClass('text-center')->sortable(false),
            Column::computed('created_time')->title('الوقت')->addClass('text-center')->sortable(false),
            Column::computed('action')->title('الإجراءات')->exportable(false)->printable(false)->width(100)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'ViewerTickets_' . date('YmdHis');
    }
}
