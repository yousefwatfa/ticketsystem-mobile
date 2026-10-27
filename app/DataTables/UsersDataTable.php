<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UsersDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<User> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('fullname_formatted', function($row){
                $html = '<div class="d-flex flex-column">';
                $html .= '<span class="fw-bold">'.$row->fullname.'</span>';
                if($row->username) {
                    $html .= '<small class="text-muted">'.$row->username.'</small>';
                }
                $html .= '</div>';
                return $html;
            })
            ->addColumn('email_formatted', function($row){
                return '<a href="mailto:'.$row->email.'" class="text-dark text-hover-primary">'.$row->email.'</a>';
            })
            ->addColumn('action', function($row){
            return '<a href="'.route('admin.users.edit', $row->id).'"
                    class="btn btn-sm btn-light-primary d-inline-flex align-items-center gap-1"
                    title="تعديل المستخدم">
                        تعديل
                    </a>';            })
            ->addColumn('role', function($row){
                $roleName = $row->role->name ?? 'بدون دور';
                $badgeClass = match($roleName) {
                    'Admin' => 'badge-light-danger',
                    'Member' => 'badge-light-primary',
                    'Viewer' => 'badge-light-info',
                    default => 'badge-secondary'
                };
                return '<span class="badge '.$badgeClass.' fs-7 fw-bold">'.$roleName.'</span>';
            })
            ->rawColumns(['action', 'role', 'fullname_formatted', 'email_formatted'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<User>
     */
    public function query(User $model): QueryBuilder
    {
        $query = $model->newQuery()->with('role');

        // Add search functionality
        if (request('search') && request('search')['value']) {
            $search = request('search')['value'];
            $query->where(function($q) use ($search) {
                $q->where('fullname', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('username', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('users-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->addTableClass('table align-middle table-row-dashed fs-6 gy-5')
                    ->orderBy(1)
                    ->parameters([
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
            Column::computed('fullname_formatted')->title('الاسم'),
            Column::computed('email_formatted')->title('البريد الإلكتروني'),
            Column::computed('role')->title('الدور')->addClass('text-center'),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(100)
                  ->addClass('text-center')
                  ->title('الاجراءات'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Users_' . date('YmdHis');
    }
}
