<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PayrollExport implements FromCollection, WithMapping, WithHeadings
{
    use Exportable;

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $salaries = Payroll::companywithdept(admin()->company_id)
            ->select(
                'employees.employeeID',
                'full_name',
                'department.name as deptName',
                'designation.designation as designationName',
                'payrolls.basic as basic',
                'overtime_hours',
                'overtime_pay',
                'total_allowance',
                'deductions',
                'total_deduction',
                'net_salary'
            )
            ->where('month', '=', request()->get('month'))->where('year', '=', request()->get('year'))->get();
        return $salaries;
    }
    /**
     * @var Invoice $invoice
     */
    public function map($payroll): array
    {
        // Decodifica el JSON en la columna "deductions" y lo convierte en un array asociativo
        $deductions = json_decode($payroll->deductions, true);

        // Formatea los valores de las deducciones
        $formattedDeductions = [];
        foreach ($deductions as $deductionType => $amount) {
        $formattedDeductions[] = "$deductionType: $amount";
        }
        $employee = Employee::where('employeeID', $payroll->employeeID)->first();
        return [
            $payroll->employeeID,
            $employee->decryptToCollection()->full_name,
            $payroll->deptName,
            $payroll->designationName,
            $payroll->basic,
            $payroll->overtime_hours,
            $payroll->overtime_pay,
            $payroll->total_allowance,
            implode(', ', $formattedDeductions),
            $payroll->total_deduction,
            $payroll->net_salary,

        ];
    }

    public function headings(): array
    {
        $monthName = date('F', mktime(0, 0, 0, request()->get('Mes'), 10)); // March
        return [
            [
                admin()->company->company_name,
            ],
            ['Reporte de Planilla'],
            ['Periodo:', $monthName . ',' . request()->get('month')],
            ['Fecha de planilla:', date('d/m/Y, g:i a')],
            [],
            [],
            [
                'Employee ID', 'Employee Name', 'Department', 'Designation', 'Basic Salary', 'Total hours',
                'Total Hourly Payment', 'Total Allowance','Deduction', 'Total Deduction', 'Net Salary'
            ]
        ];
    }


    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],

            // Styling a specific cell by coordinate.
            'B2' => ['font' => ['italic' => true]],

            // Styling an entire column.
            'C'  => ['font' => ['size' => 16]],
        ];
    }
}
