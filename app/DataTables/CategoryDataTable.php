<?php

namespace App\DataTables;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CategoryDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('name', function($row) {
                return '
                    <div class="d-flex flex-column">
                        <span class="text-gray-800 text-hover-primary mb-1 fw-bold">' . $row->name . '</span>
                    </div>';
            })

            ->addColumn('department_name', function($row) {
                if ($row->department) {
                    return '
                        <div class="badge badge-light-primary fw-bold px-4 py-3">
                            ' . $row->department->name . '
                        </div>';
                }

                return '<span class="badge badge-light-danger fw-bold px-4 py-3">لم يتم التعيين بعد</span>';
            })

            ->addColumn('action', function($row) {
                return '
                    <div class="d-flex justify-content-center gap-2">
                        <a href="'.route('admin.categories.edit', $row->id).'" class="btn btn-light btn-active-light-primary btn-sm">
                            <span class="fw-bold fs-9">تعديل</span>
                        </a>

                        <form action="'.route('admin.categories.destroy', $row->id).'" method="POST">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-light btn-active-light-danger btn-sm delete-btn">
                                <span class="fw-bold fs-9">حذف</span>
                            </button>
                        </form>
                    </div>';
            })
            ->rawColumns(['name', 'department_name', 'action'])
            ->setRowId('id');
    }

    public function query(Category $model): QueryBuilder
    {
        return $model->newQuery()->with('department')->latest();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('categories-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()

                    ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
                    ->parameters([
                        'language' => ['url' => asset('assets/js/ar.json')],
                        'drawCallback' => 'function() { KTMenu.createInstances(); }',
                        'responsive' => true,
                        'autoWidth' => false,
                    ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('#')
                ->width(50)
                ->addClass('text-center fw-bold text-gray-800'),
            Column::make('name')
                ->title('المشكلة')
                ->addClass('text-end fw-bold text-gray-800'),

            Column::computed('department_name')
                ->title('القسم المسؤول')
                ->addClass('text-center fw-bold text-gray-800'),

            Column::computed('action')
                ->title('الإجراءات')
                ->addClass('text-center fw-bold text-gray-800')
                ->width(50),
        ];
    }
}
