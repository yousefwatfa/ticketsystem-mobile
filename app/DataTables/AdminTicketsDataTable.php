<?php

namespace App\DataTables;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AdminTicketsDataTable extends DataTable
{
public function dataTable(QueryBuilder $query): EloquentDataTable
{
        $userHospitalsIds = auth()->user()->hospitals()->pluck('hospital_id')->toArray();

        $baseFilterQuery = Ticket::query()
           
        ->whereHas('creator.hospitals', function ($q) use ($userHospitalsIds) {
            $q->whereIn('hospital_id', $userHospitalsIds);
        })
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
        ->addColumn('creator_fullname', function($row) {
    $output = '';

    if ($row->creator) {
        $output .= '<div class="d-flex flex-column">';
        $output .= '<a href="javascript:void(0)" class="text-dark view-user-details"  data-user-id="' . $row->creator->id . '">' . e($row->creator->fullname) . '</a>';

        if ($row->on_behalf_of && $row->onBehalfUser) {
            $output .= '<span class="badge badge-light-info fs-8 py-1 mt-1" style="width:fit-content">' .
                       '<i class="bi bi-person-fill fs-9 me-1"></i> عن: ' .
                       e($row->onBehalfUser->fullname) .
                       '</span>';
        }

        $output .= '</div>';
        return $output;
    }

    return '<span class="text-muted">-</span>';
})
        ->addColumn('category_name', function($row) {
            return $row->category ? $row->category->name : '<span class="text-muted">-</span>';
        })
        ->addColumn('department_name', function($row) {
                return $row->category->department ? $row->category->department->name : '<span class="text-muted">-</span>';
                })

        ->filterColumn('creator_fullname', function($query, $keyword) {
            $query->whereHas('creator', function($q) use ($keyword) {
                $q->where('fullname', 'like', "%{$keyword}%");
            });
        })
        ->filterColumn('category_name', function($query, $keyword) {
            $query->whereHas('category', function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            });
        })
        ->filterColumn('id', function ($query, $keyword) {
            $normalizedKeyword = trim(str_replace('#', '', $keyword));
            if (is_numeric($normalizedKeyword)) {
                $query->where('id', (int) $normalizedKeyword);
            }
        })->filterColumn('description', function ($query, $keyword) {
            $query->where('description', 'like', "%{$keyword}%");
        })
        
       ->addColumn('assigned_to', function($row){
                $users = $row->assignedUsers->unique('id'); // Ensure uniqueness just in case
                if ($users->isEmpty()) {
                    return '<span class="badge badge-light">غير معين</span>';
                }
                $badges = $users->map(function($user) {
                    return '<span class="badge badge-light-info me-1">' . $user->fullname . '</span>';
                })->implode('');
                return $badges;
            })
            ->editColumn('status', function($row){
                return '<span class="badge '.$row->status_badge_class.'">'.$row->status_label.'</span>';
            })
        ->addColumn('action', function($row){

            $showBtn = '<button data-id="'.$row->id.'" class="btn btn-light-primary btn-sm me-1 view-ticket-popup">عرض</button>';
            $manageBtn = '<a href="'.route('admin.tickets.show', $row->id).'" class="btn btn-light-info btn-sm me-2">إدارة</a>';
            return $showBtn  . (auth()->user()->can('update', $row) ? $manageBtn : '');

            })->editColumn('created_at', function($row){
                return $row->created_at ? $row->created_at->format('Y-m-d H:i') : '';
            })
            ->with('updatedCounts', [
            'all'         => (clone $baseFilterQuery)->count(),
            'open'        => (clone $baseFilterQuery)->where('status', 'open')->count(),
            'in_progress' => (clone $baseFilterQuery)->where('status', 'in_progress')->count(),
            'pending'     => (clone $baseFilterQuery)->where('status', 'pending')->count(),
            'finished'    => (clone $baseFilterQuery)->where('status', 'finished')->count(),
            'closed'      => (clone $baseFilterQuery)->where('status', 'closed')->count(),
        ])
        ->rawColumns(['status', 'creator_fullname','assigned_to', 'created_at','action'])
        ->setRowId('id');
}

public function query(Ticket $model): QueryBuilder
{
        $userHospitalsIds = auth()->user()->hospitals()->pluck('hospital_id')->toArray();

        return $model->newQuery()
            ->whereHas('creator.hospitals', function ($query) use ($userHospitalsIds) {
                $query->whereIn('hospital_id', $userHospitalsIds);
            })
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
        ->with(['category', 'creator', 'assignedUsers','onBehalfUser'])
        ->latest();
}

public function html(): HtmlBuilder
{
    return $this->builder()
        ->setTableId('admin-tickets-table')
        ->columns($this->getColumns())
        // ->dom('Bfrtip')
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
                        var table = window.LaravelDataTables['admin-tickets-table'];
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
            Column::make('id')->title('رقم التذكرة')->addClass('text-center')->orderable(false),
            Column::make('title')->title('العنوان')->orderable(false),
            Column::make('description')->title('الوصف')->visible(false)->searchable(true),
            Column::make('creator_fullname')->title('المستخدم')->data('creator_fullname')->orderable(false),
            Column::make('category_name')->title('المشكلة')->data('category_name')->orderable(false),
            Column::make('department_name')->title('القسم')->sortable(false),
            Column::make('office_location')->title('عنوان القسم')->sortable(false),
            Column::make('status')->title('الحالة')->addClass('text-center')->orderable(false),
            Column::computed('assigned_to')->title('تم تعيينه لـ')->orderable(false),
            Column::make('created_at')->title('تاريخ الإنشاء')->addClass('text-center')->orderable(false),
            Column::computed('action')->title('الإجراءات')->addClass('text-center')->width(220)->orderable(false),        ];

    }

    protected function filename(): string
    {
        return 'AdminTickets_' . date('YmdHis');
    }
}
