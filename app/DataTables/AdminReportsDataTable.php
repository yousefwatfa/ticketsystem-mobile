<?php

namespace App\DataTables;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AdminReportsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('status', function ($row) {
                return '<span class="badge ' . $row->status_badge_class . '">' . $row->status_label . '</span>';
            })
            ->addColumn('assigned_to', function ($row) {
                $users = $row->assignedUsers->unique('id');
                if ($users->isEmpty()) {
                    return '<span class="badge badge-light">غير معين</span>';
                }

                return $users->map(function ($user) {
                    return '<span class="badge badge-light-info me-1">' . $user->fullname . '</span>';
                })->implode('');
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? $row->created_at->format('Y-m-d H:i') : '';
            })
            ->rawColumns(['status', 'assigned_to', 'created_at'])
            ->setRowId('id');
    }

    public function query(Ticket $model): QueryBuilder
    {
        $currentUser = auth()->user();
        $query = $model->newQuery()->whereIn('status', ['closed', 'finished']);

        $searchTriggered = request()->has('search_triggered') && request('search_triggered') == 'true';

        $filterValues = request()->only(['from_date', 'to_date', 'title', 'department_id', 'main_department_id', 'category', 'user_id']);
        $activeFilters = array_filter($filterValues, function ($value) {
            return !is_null($value) && $value !== '' && $value !== 'all';
        });

        if (!$searchTriggered && empty($activeFilters)) {
            return $query->where($model->qualifyColumn('id'), 0);
        }


        if (!$currentUser->is_root) {
            $userMainDepts = $currentUser->departments()->pluck('main_department_id');
            $query->whereHas('category.department', function ($q) use ($userMainDepts) {
                $q->whereIn('main_department_id', $userMainDepts);
            });
        }

        if ($currentUser->is_root && request()->filled('main_department_id') && request('main_department_id') !== 'all') {
            $query->whereHas('category.department', function ($q) {
                $q->where('main_department_id', request('main_department_id'));
            });
        }

        if (request()->filled('department_id') && request('department_id') !== 'all') {
            $query->whereHas('category.department', function ($q) {
                $q->where('id', request('department_id'));
            });
        }


        if (request()->filled('user_id') && request('user_id') !== 'all') {
            $selectedUserId = request('user_id');
            $query->whereHas('assignedUsers', function ($q) use ($selectedUserId) {
                $q->where('users.id', $selectedUserId);
            });
        }


        if (request()->filled('title')) {
            $searchTerm = trim((string) request('title'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('id', str_replace('#', '', $searchTerm));
            });
        }

        if (request()->filled('category')) {
            $query->whereHas('category', function ($q) {
                $q->where('name', 'like', '%' . request('category') . '%');
            });
        }

        if (request()->filled('from_date')) {
            $query->whereDate('created_at', '>=', request('from_date'));
        }

        if (request()->filled('to_date')) {
            $query->whereDate('created_at', '<=', request('to_date'));
        }

        return $query->with(['category', 'category.department', 'creator', 'assignedUsers'])->latest();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('admin-reports-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', "
          data.search_triggered = $('#search_triggered_hidden').val(); // نأخذ القيمة من حقل مخفي
            data.title = $('#title').val();
            data.user_id = $('#user_id').val();
            data.main_department_id = $('#main_department_filter').val();
            data.department_id = $('#department_filter').val();
            data.from_date = $('#from_date').val();
            data.to_date = $('#to_date').val();
            data.category = $('#category').val();
")
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5')
            ->parameters([
                'language' => ['url' => asset('assets/js/ar.json')],
                'searching' => false,
                'dom' => 'rtip',
                'buttons' => [
                    ['extend' => 'excel', 'className' => 'd-none'],
                ],
                'drawCallback' => "function(settings) {
                    var api = this.api();
                    var count = api.page.info().recordsDisplay;
                    $('#results-count').html(count);
                }",
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#'),
            Column::make('title')->title('العنوان'),
            Column::make('creator.fullname')->title('المستخدم')->sortable(false),
            Column::make('category.name')->title('المشكلة'),
            Column::make('status')->title('الحالة'),
            Column::computed('assigned_to')->title('تم تعيينه لـ')->sortable(false),
            Column::make('created_at')->title('التاريخ'),
        ];
    }
}
