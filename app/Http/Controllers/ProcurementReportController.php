<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcurementReportController extends Controller
{
    public function index()
    {
        $periods = DB::table('purchase_requests')
            ->selectRaw('YEAR(date_pr) as year, MONTH(date_pr) as month')
            ->whereNotNull('date_pr')
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return view('modules.procurement.report', compact('periods'));
    }

    public function generate(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if (!$year) {
            return redirect()->route('procurement.report.index')
                ->with('error', 'Please select a year to generate the report.');
        }

        $reportData = $this->getProcurementData($month, $year);

        $periods = DB::table('purchase_requests')
            ->selectRaw('YEAR(date_pr) as year, MONTH(date_pr) as month')
            ->whereNotNull('date_pr')
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return view('modules.procurement.report', compact('reportData', 'periods', 'month', 'year'));
    }

    public function exportCsv(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $reportData = $this->getProcurementData($month, $year);

        $monthName = $month ? date('F Y', mktime(0, 0, 0, $month, 1, $year)) : 'Full Year ' . $year;
        $filename = 'PMR_' . $year . '_' . ($month ? str_pad($month, 2, '0', STR_PAD_LEFT) : 'FullYear') . '_' . date('YmdHis') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($reportData, $monthName) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for proper Excel opening
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // ============================================
            // HEADER SECTION - Enhanced with separators
            // ============================================
            fputcsv($file, ['='.str_repeat('=', 100)]);
            fputcsv($file, ['DEPARTMENT OF SCIENCE AND TECHNOLOGY']);
            fputcsv($file, ['Procurement Monitoring Report as of ' . $monthName]);
            fputcsv($file, ['CORDILLERA ADMINISTRATIVE REGION']);
            fputcsv($file, ['='.str_repeat('=', 100)]);
            fputcsv($file, []);

            // ============================================
            // COLUMN HEADERS - Main categories
            // ============================================
            fputcsv($file, [
                'PR No.',
                'Supplier',
                'Code (PAP)',
                'Procurement Project',
                'PMO/End-User',
                'Early Procurement?',
                'Mode of Procurement',
                // Procurement Activities (13 columns)
                'Pre-Proc Conference',
                'Ads/Post of IB',
                'Pre-bid Conf',
                'Eligibility Check',
                'Sub/Open of Bids',
                'Bid Evaluation',
                'Post Qual',
                'BAC Resolution',
                'Notice of Award',
                'Contract Signing',
                'Notice to Proceed',
                'Delivery/Completion',
                'Inspection & Acceptance',
                // Funds and Costs
                'Source of Funds',
                'ABC Total',
                'ABC MOOE',
                'ABC CO',
                'Contract Total',
                'Contract MOOE',
                'Contract CO',
                // Additional Info
                'Reference PR',
                'Reference Supplier',
                'Remarks'
            ]);

            // Separator line
            fputcsv($file, array_fill(0, 30, '---'));

            // ============================================
            // DATA ROWS - With improved formatting
            // ============================================
            $rowNumber = 1;
            foreach ($reportData as $item) {
                fputcsv($file, [
                    $item->pr_no ?? '',
                    $item->supplier ?? '',
                    '', // Code (PAP) - empty as per original
                    $item->purpose ?? '',
                    $item->division_name ?? '',
                    'No', // Early Procurement
                    $item->mode_procurement_name ?? '',
                    // Procurement Activity Dates
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_abstract_approved),
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_canvass),
                    $this->formatDateLong($item->date_iar),
                    $this->formatDateLong($item->date_received),
                    // Funding
                    'Government of the Philippines (current year\'s budget)',
                    // ABC Costs
                    number_format($item->total_abc ?? 0, 2, '.', ','),
                    number_format($item->total_mooe ?? 0, 2, '.', ','),
                    number_format($item->total_co ?? 0, 2, '.', ','),
                    // Contract Costs
                    number_format($item->contract_total ?? 0, 2, '.', ','),
                    number_format($item->contract_mooe ?? 0, 2, '.', ','),
                    number_format($item->contract_co ?? 0, 2, '.', ','),
                    // References
                    $item->pr_no ?? '',
                    $item->supplier ?? '',
                    $item->remarks ?? ''
                ]);
                $rowNumber++;
            }

            // ============================================
            // FOOTER SECTION - Summary
            // ============================================
            fputcsv($file, []);
            fputcsv($file, ['='.str_repeat('=', 100)]);
            fputcsv($file, ['SUMMARY']);
            fputcsv($file, ['Total Records: ' . count($reportData)]);

            // Calculate totals
            $totalABC = $reportData->sum('total_abc');
            $totalContract = $reportData->sum('contract_total');

            fputcsv($file, ['Total ABC Amount: ₱' . number_format($totalABC, 2, '.', ',')]);
            fputcsv($file, ['Total Contract Amount: ₱' . number_format($totalContract, 2, '.', ',')]);
            fputcsv($file, ['Generated: ' . date('F d, Y h:i A')]);
            fputcsv($file, ['='.str_repeat('=', 100)]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportExcel(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $reportData = $this->getProcurementData($month, $year);

        $monthName = $month ? date('F Y', mktime(0, 0, 0, $month, 1, $year)) : 'Full Year ' . $year;
        $filename = 'PMR_' . $year . '_' . ($month ? str_pad($month, 2, '0', STR_PAD_LEFT) : 'FullYear') . '_' . date('YmdHis') . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($reportData, $monthName) {
            echo $this->generateExcelHtml($reportData, $monthName);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function generateExcelHtml($reportData, $monthName)
    {
        $html = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?>';
        $html .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
        $html .= 'xmlns:o="urn:schemas-microsoft-com:office:office" ';
        $html .= 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
        $html .= 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';

        // ============================================
        // ENHANCED STYLES
        // ============================================
        $html .= '<Styles>';

        // Main header style (Title)
        $html .= '<Style ss:ID="headerTitle">';
        $html .= '<Font ss:Bold="1" ss:Size="14" ss:Color="#FFFFFF"/>';
        $html .= '<Interior ss:Color="#1F4E78" ss:Pattern="Solid"/>';
        $html .= '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        $html .= '<Borders>';
        $html .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>';
        $html .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>';
        $html .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2"/>';
        $html .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2"/>';
        $html .= '</Borders>';
        $html .= '</Style>';

        // Sub header style
        $html .= '<Style ss:ID="headerSub">';
        $html .= '<Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF"/>';
        $html .= '<Interior ss:Color="#2E75B5" ss:Pattern="Solid"/>';
        $html .= '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
        $html .= '</Style>';

        // Column header style (main categories)
        $html .= '<Style ss:ID="columnHeader">';
        $html .= '<Font ss:Bold="1" ss:Size="10" ss:Color="#FFFFFF"/>';
        $html .= '<Interior ss:Color="#4472C4" ss:Pattern="Solid"/>';
        $html .= '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        $html .= '<Borders>';
        $html .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>';
        $html .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>';
        $html .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '</Borders>';
        $html .= '</Style>';

        // Sub column header style
        $html .= '<Style ss:ID="subColumnHeader">';
        $html .= '<Font ss:Bold="1" ss:Size="9" ss:Color="#FFFFFF"/>';
        $html .= '<Interior ss:Color="#5B9BD5" ss:Pattern="Solid"/>';
        $html .= '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>';
        $html .= '<Borders>';
        $html .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>';
        $html .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '</Borders>';
        $html .= '</Style>';

        // Data cell style (alternating rows)
        $html .= '<Style ss:ID="dataCell">';
        $html .= '<Font ss:Size="9"/>';
        $html .= '<Borders>';
        $html .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '</Borders>';
        $html .= '<Alignment ss:Vertical="Top" ss:WrapText="1"/>';
        $html .= '</Style>';

        // Alternate row style
        $html .= '<Style ss:ID="dataCellAlt">';
        $html .= '<Font ss:Size="9"/>';
        $html .= '<Interior ss:Color="#E7E6E6" ss:Pattern="Solid"/>';
        $html .= '<Borders>';
        $html .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '</Borders>';
        $html .= '<Alignment ss:Vertical="Top" ss:WrapText="1"/>';
        $html .= '</Style>';

        // Number cell style
        $html .= '<Style ss:ID="numberCell">';
        $html .= '<Font ss:Size="9"/>';
        $html .= '<NumberFormat ss:Format="#,##0.00"/>';
        $html .= '<Borders>';
        $html .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '</Borders>';
        $html .= '<Alignment ss:Horizontal="Right" ss:Vertical="Top"/>';
        $html .= '</Style>';

        // Number cell alternate
        $html .= '<Style ss:ID="numberCellAlt">';
        $html .= '<Font ss:Size="9"/>';
        $html .= '<NumberFormat ss:Format="#,##0.00"/>';
        $html .= '<Interior ss:Color="#E7E6E6" ss:Pattern="Solid"/>';
        $html .= '<Borders>';
        $html .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '</Borders>';
        $html .= '<Alignment ss:Horizontal="Right" ss:Vertical="Top"/>';
        $html .= '</Style>';

        // Date cell style
        $html .= '<Style ss:ID="dateCell">';
        $html .= '<Font ss:Size="9"/>';
        $html .= '<Borders>';
        $html .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '</Borders>';
        $html .= '<Alignment ss:Horizontal="Center" ss:Vertical="Top"/>';
        $html .= '</Style>';

        // Footer style
        $html .= '<Style ss:ID="footer">';
        $html .= '<Font ss:Bold="1" ss:Size="10"/>';
        $html .= '<Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/>';
        $html .= '<Alignment ss:Horizontal="Left" ss:Vertical="Center"/>';
        $html .= '<Borders>';
        $html .= '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>';
        $html .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
        $html .= '</Borders>';
        $html .= '</Style>';

        $html .= '</Styles>';

        $html .= '<Worksheet ss:Name="PMR">';
        $html .= '<Table ss:DefaultRowHeight="15">';

        // Set column widths
        $html .= '<Column ss:Width="80"/>'; // PR No
        $html .= '<Column ss:Width="150"/>'; // Supplier
        $html .= '<Column ss:Width="60"/>'; // Code
        $html .= '<Column ss:Width="200"/>'; // Project
        $html .= '<Column ss:Width="120"/>'; // PMO
        $html .= '<Column ss:Width="60"/>'; // Early Proc
        $html .= '<Column ss:Width="120"/>'; // Mode
        // Activity dates (13 columns)
        for ($i = 0; $i < 13; $i++) {
            $html .= '<Column ss:Width="85"/>';
        }
        $html .= '<Column ss:Width="180"/>'; // Source of Funds
        // ABC and Contract costs (6 columns)
        for ($i = 0; $i < 6; $i++) {
            $html .= '<Column ss:Width="90"/>';
        }
        $html .= '<Column ss:Width="80"/>'; // PR Ref
        $html .= '<Column ss:Width="150"/>'; // Supplier Ref
        $html .= '<Column ss:Width="150"/>'; // Remarks

        // ============================================
        // HEADER ROWS
        // ============================================
        $html .= '<Row ss:Height="30">';
        $html .= '<Cell ss:MergeAcross="29" ss:StyleID="headerTitle"><Data ss:Type="String">DEPARTMENT OF SCIENCE AND TECHNOLOGY</Data></Cell>';
        $html .= '</Row>';

        $html .= '<Row ss:Height="25">';
        $html .= '<Cell ss:MergeAcross="29" ss:StyleID="headerSub"><Data ss:Type="String">Procurement Monitoring Report as of ' . htmlspecialchars($monthName) . '</Data></Cell>';
        $html .= '</Row>';

        $html .= '<Row ss:Height="25">';
        $html .= '<Cell ss:MergeAcross="29" ss:StyleID="headerSub"><Data ss:Type="String">CORDILLERA ADMINISTRATIVE REGION</Data></Cell>';
        $html .= '</Row>';

        $html .= '<Row ss:Height="5"></Row>';

        // ============================================
        // COLUMN HEADERS - Row 1 (with merges)
        // ============================================
        $html .= '<Row ss:Height="50">';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">PR No.</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">Supplier</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">Code (PAP)</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">Procurement Project</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">PMO/End-User</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">Is this an Early Procurement Activity?</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">Mode of Procurement</Data></Cell>';
        $html .= '<Cell ss:MergeAcross="12" ss:StyleID="columnHeader"><Data ss:Type="String">Actual Procurement Activities</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">Source of Funds</Data></Cell>';
        $html .= '<Cell ss:MergeAcross="2" ss:StyleID="columnHeader"><Data ss:Type="String">ABC (PhP)</Data></Cell>';
        $html .= '<Cell ss:MergeAcross="2" ss:StyleID="columnHeader"><Data ss:Type="String">Contract Cost (PhP)</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">PR No.</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">Supplier</Data></Cell>';
        $html .= '<Cell ss:MergeDown="1" ss:StyleID="columnHeader"><Data ss:Type="String">Remarks</Data></Cell>';
        $html .= '</Row>';

        // Column headers - Row 2 (sub-headers)
        $html .= '<Row ss:Height="45">';
        // Empty cells for merged cells above
        $html .= str_repeat('<Cell ss:Index="8"/>', 0); // Start at column 8
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Pre-Proc Conference</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Ads/Post of IB</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Pre-bid Conf</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Eligibility Check</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Sub/Open of Bids</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Bid Evaluation</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Post Qual</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Date of BAC Resolution Recommending Award</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Notice of Award</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Contract Signing</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Notice to Proceed</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Delivery/Completion</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Inspection &amp; Acceptance</Data></Cell>';
        // Skip merged source of funds
        $html .= '<Cell ss:Index="22"/>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Total</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">MOOE</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">CO</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">Total</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">MOOE</Data></Cell>';
        $html .= '<Cell ss:StyleID="subColumnHeader"><Data ss:Type="String">CO</Data></Cell>';
        $html .= '</Row>';

        // ============================================
        // DATA ROWS with alternating colors
        // ============================================
        $rowNum = 0;
        foreach ($reportData as $item) {
            $isAlt = ($rowNum % 2 == 1);
            $dataStyle = $isAlt ? 'dataCellAlt' : 'dataCell';
            $numberStyle = $isAlt ? 'numberCellAlt' : 'numberCell';

            $html .= '<Row ss:Height="25">';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">' . htmlspecialchars($item->pr_no ?? '') . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">' . htmlspecialchars($item->supplier ?? '') . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String"></Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">' . htmlspecialchars($item->purpose ?? '') . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">' . htmlspecialchars($item->division_name ?? '') . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">No</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">' . htmlspecialchars($item->mode_procurement_name ?? '') . '</Data></Cell>';

            // Date columns with actual data
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_abstract_approved) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_canvass) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_iar) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="dateCell"><Data ss:Type="String">' . $this->formatDateLong($item->date_received) . '</Data></Cell>';

            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">Government of the Philippines (current year\'s budget)</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $numberStyle . '"><Data ss:Type="Number">' . ($item->total_abc ?? 0) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $numberStyle . '"><Data ss:Type="Number">' . ($item->total_mooe ?? 0) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $numberStyle . '"><Data ss:Type="Number">' . ($item->total_co ?? 0) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $numberStyle . '"><Data ss:Type="Number">' . ($item->contract_total ?? 0) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $numberStyle . '"><Data ss:Type="Number">' . ($item->contract_mooe ?? 0) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $numberStyle . '"><Data ss:Type="Number">' . ($item->contract_co ?? 0) . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">' . htmlspecialchars($item->pr_no ?? '') . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">' . htmlspecialchars($item->supplier ?? '') . '</Data></Cell>';
            $html .= '<Cell ss:StyleID="' . $dataStyle . '"><Data ss:Type="String">' . htmlspecialchars($item->remarks ?? '') . '</Data></Cell>';
            $html .= '</Row>';

            $rowNum++;
        }

        // ============================================
        // FOOTER/SUMMARY SECTION
        // ============================================
        $totalABC = 0;
        $totalContract = 0;
        foreach ($reportData as $item) {
            $totalABC += $item->total_abc ?? 0;
            $totalContract += $item->contract_total ?? 0;
        }

        $html .= '<Row ss:Height="5"></Row>';
        $html .= '<Row ss:Height="25">';
        $html .= '<Cell ss:MergeAcross="29" ss:StyleID="footer"><Data ss:Type="String">SUMMARY</Data></Cell>';
        $html .= '</Row>';

        $html .= '<Row ss:Height="20">';
        $html .= '<Cell ss:MergeAcross="3" ss:StyleID="footer"><Data ss:Type="String">Total Records:</Data></Cell>';
        $html .= '<Cell ss:MergeAcross="25" ss:StyleID="dataCell"><Data ss:Type="String">' . count($reportData) . '</Data></Cell>';
        $html .= '</Row>';

        $html .= '<Row ss:Height="20">';
        $html .= '<Cell ss:MergeAcross="3" ss:StyleID="footer"><Data ss:Type="String">Total ABC Amount:</Data></Cell>';
        $html .= '<Cell ss:MergeAcross="25" ss:StyleID="numberCell"><Data ss:Type="String">₱ ' . number_format($totalABC, 2) . '</Data></Cell>';
        $html .= '</Row>';

        $html .= '<Row ss:Height="20">';
        $html .= '<Cell ss:MergeAcross="3" ss:StyleID="footer"><Data ss:Type="String">Total Contract Amount:</Data></Cell>';
        $html .= '<Cell ss:MergeAcross="25" ss:StyleID="numberCell"><Data ss:Type="String">₱ ' . number_format($totalContract, 2) . '</Data></Cell>';
        $html .= '</Row>';

        $html .= '<Row ss:Height="20">';
        $html .= '<Cell ss:MergeAcross="3" ss:StyleID="footer"><Data ss:Type="String">Generated:</Data></Cell>';
        $html .= '<Cell ss:MergeAcross="25" ss:StyleID="dataCell"><Data ss:Type="String">' . date('F d, Y h:i A') . '</Data></Cell>';
        $html .= '</Row>';

        $html .= '</Table></Worksheet></Workbook>';
        return $html;
    }

    private function getProcurementData($month = null, $year = null)
    {
        $query = DB::table('purchase_requests as pr')
            ->leftJoin('emp_divisions as ed', 'pr.division', '=', 'ed.id')
            ->leftJoin('request_quotations as rq', 'pr.id', '=', 'rq.pr_id')
            ->leftJoin('abstract_quotations as aq', 'pr.id', '=', 'aq.pr_id')
            ->leftJoin('mooe_classifications as mc', 'aq.mode_procurement', '=', 'mc.id')
            ->leftJoin('inspection_acceptance_reports as iar', 'pr.id', '=', 'iar.pr_id')
            ->select(
                'pr.id',
                'pr.pr_no',
                'pr.office',
                'pr.division',
                'ed.division_name',
                'pr.purpose',
                'pr.remarks',
                'pr.status',
                'pr.funding_source',
                'pr.date_pr',
                'aq.mode_procurement',
                'mc.classification_name as mode_procurement_name',
                'rq.date_canvass',
                'aq.date_abstract',
                'aq.date_abstract_approved',
                'iar.date_iar',
                'iar.date_inspected',
                'iar.date_received'
            )
            ->whereNotNull('pr.date_pr')
            ->groupBy(
                'pr.id', 'pr.pr_no', 'pr.office', 'pr.division', 'ed.division_name',
                'pr.purpose', 'pr.remarks', 'pr.status', 'pr.funding_source', 'pr.date_pr',
                'aq.mode_procurement', 'mc.classification_name',
                'rq.date_canvass', 'aq.date_abstract', 'aq.date_abstract_approved',
                'iar.date_iar', 'iar.date_inspected', 'iar.date_received'
            );

        if ($year) {
            $query->whereYear('pr.date_pr', '=', $year);
            if ($month) {
                $query->whereMonth('pr.date_pr', '=', $month);
            }
        }

        $query->orderBy('pr.pr_no', 'asc');
        $baseResults = $query->get();

        $results = collect();

        foreach ($baseResults as $item) {
            $prItems = DB::table('purchase_request_items')
                ->where('pr_id', $item->id)
                ->select(DB::raw('SUM(est_total_cost) as total_abc'))
                ->first();

            $aqItems = DB::table('abstract_quotation_items as aqi')
                ->leftJoin('abstract_quotations as aq', 'aqi.abstract_id', '=', 'aq.id')
                ->leftJoin('suppliers as s', 'aqi.supplier', '=', 's.id')
                ->where('aq.pr_id', $item->id)
                ->select(
                    DB::raw('SUM(aqi.total_cost) as contract_total'),
                    DB::raw('GROUP_CONCAT(DISTINCT s.company_name SEPARATOR ", ") as supplier_names')
                )
                ->first();

            $result = (object)[
                'id' => $item->id,
                'pr_no' => $item->pr_no,
                'office' => $item->office,
                'division' => $item->division,
                'division_name' => $item->division_name ?? '',
                'purpose' => $item->purpose,
                'remarks' => $item->remarks,
                'status' => $item->status,
                'funding_source' => $item->funding_source,
                'mode_procurement' => $item->mode_procurement,
                'mode_procurement_name' => $item->mode_procurement_name ?? '',
                'date_canvass' => $item->date_canvass,
                'date_abstract' => $item->date_abstract,
                'date_abstract_approved' => $item->date_abstract_approved,
                'date_iar' => $item->date_iar,
                'date_inspected' => $item->date_inspected,
                'date_received' => $item->date_received,
                'total_abc' => $prItems->total_abc ?? 0,
                'total_mooe' => $prItems->total_abc ?? 0,
                'total_co' => 0,
                'contract_total' => $aqItems->contract_total ?? 0,
                'contract_mooe' => $aqItems->contract_total ?? 0,
                'contract_co' => 0,
                'supplier' => $aqItems->supplier_names ?? ''
            ];

            if (empty($result->status)) {
                $result->status = $result->date_iar ? 'Completed' : 'Ongoing';
            }

            if (empty($result->remarks)) {
                $result->remarks = $result->status;
            }

            $results->push($result);
        }

        return $results;
    }

    /**
     * Format date to "Month Day, Year" format
     */
    private function formatDateLong($date)
    {
        if (!$date) return '';
        return date('F d, Y', strtotime($date));
    }

    public function debug(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');

        $prCount = DB::table('purchase_requests')
            ->whereYear('date_pr', '=', $year)
            ->count();

        $samplePR = DB::table('purchase_requests as pr')
            ->leftJoin('emp_divisions as ed', 'pr.division', '=', 'ed.id')
            ->leftJoin('abstract_quotations as aq', 'pr.id', '=', 'aq.pr_id')
            ->leftJoin('mooe_classifications as mc', 'aq.mode_procurement', '=', 'mc.id')
            ->whereYear('pr.date_pr', '=', $year)
            ->select('pr.*', 'ed.division_name', 'mc.classification_name as mode_procurement_name')
            ->limit(3)
            ->get();

        $debug = [
            'year' => $year,
            'month' => $month,
            'purchase_requests_count' => $prCount,
            'sample_pr' => $samplePR,
            'suppliers_count' => DB::table('suppliers')->count(),
            'emp_divisions_count' => DB::table('emp_divisions')->count(),
            'mooe_classifications_count' => DB::table('mooe_classifications')->count(),
            'sample_divisions' => DB::table('emp_divisions')
                ->select('id', 'division_name')
                ->limit(10)
                ->get(),
            'sample_classifications' => DB::table('mooe_classifications')
                ->select('id', 'classification_name')
                ->limit(10)
                ->get()
        ];

        return response()->json($debug);
    }
}
