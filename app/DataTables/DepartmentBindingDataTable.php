<?php

namespace App\DataTables;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DepartmentBindingDataTable extends DataTable
{

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('main_department', function($row){

                if ($row->mainDepartment) {
                    return '<span class="badge badge-light-primary">' . $row->mainDepartment->name . '</span>';
                }
                return '<span class="badge badge-light">قسم رئيسي</span>';
            })
                ->addColumn('action', function($row){
                    $deleteRoute = route('admin.main_departments.unbindDepartment', $row->id);
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    return '
                        <div class="text-center">
                            <form action="'.$deleteRoute.'" method="POST" class="d-inline">
                                '.$csrf.'
                                '.$method.'
                                <button type="submit" class="btn btn-light btn-active-light-danger btn-sm delete-btn">حذف</button>
                            </form>
                        </div>';
        })
            ->rawColumns(['main_department', 'action'])
            ->setRowId('id');
    }


    public function query(Department $model): QueryBuilder
    {

        return $model->newQuery()->whereNotNull('main_department_id')->with('mainDepartment');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('department-binding-table')
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

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->width(50)->addClass('text-center'),
            Column::make('name')->title('اسم القسم الفرعي'),
            Column::computed('main_department')->title('يرتبط بالقسم الرئيسي'),

            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(100)
                  ->addClass('text-center')
                  ->title('العمليات'),
        ];
    }

    protected function filename(): string
    {
        return 'DepartmentBinding_' . date('YmdHis');
    }
}
