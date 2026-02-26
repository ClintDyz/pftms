<?php

namespace App\Http\Controllers;
use TCPDF;

use Illuminate\Http\Request;
use DB;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $purchase = collect();
        $grandTotal = 0;
        $hasFilter = false;
        $filterYear = $request->filled('year') ? $request->year : now()->year;

        // Only query if month or year is provided
        if ($request->filled('month') || $request->filled('year')) {
            $hasFilter = true;

            $query = DB::table('purchase_requests as pr')
                ->leftJoin('purchase_request_items as pri', 'pr.id', '=', 'pri.pr_id')
                ->select(
                    'pr.id',
                    'pr.pr_no',
                    'pr.purpose',
                    'pr.created_at',
                    DB::raw('COALESCE(SUM(pri.est_total_cost), 0) as total_cost')
                )
                ->groupBy('pr.id', 'pr.pr_no', 'pr.purpose', 'pr.created_at');

            // Filter by month and year if provided
            if ($request->filled('month')) {
                $query->whereMonth('pr.created_at', $request->month);
            }

            if ($request->filled('year')) {
                $query->whereYear('pr.created_at', $request->year);
            }

            // Get all results without pagination
            $purchase = $query->orderBy('pr.created_at', 'desc')->get();

            // Calculate grand total
            $grandTotal = $purchase->sum('total_cost');
        }

        return view('modules.report.PurchaseRequestMonthlyReport.index', compact('purchase', 'grandTotal', 'hasFilter', 'filterYear'));
    }

    public function print(Request $request)
    {
        set_time_limit(300);

        $filterYear = $request->filled('year') ? $request->year : now()->year;

        $query = DB::table('purchase_requests as pr')
            ->leftJoin('purchase_request_items as pri', 'pr.id', '=', 'pri.pr_id')
            ->select(
                'pr.pr_no',
                'pr.purpose',
                DB::raw('COALESCE(SUM(pri.est_total_cost), 0) as total_cost'),
                DB::raw('DATE_FORMAT(pr.created_at, "%M %d, %Y") as created_date')
            )
            ->groupBy('pr.id', 'pr.pr_no', 'pr.purpose', 'pr.created_at');

        if ($request->filled('month')) {
            $query->whereMonth('pr.created_at', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('pr.created_at', $request->year);
        }

        $purchase = $query->orderBy('pr.created_at', 'desc')->get();

        $pdf = new TCPDF();
        $pdf->SetMargins(15, 20, 15);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 11);

        // HEADER
        $logo = public_path('images/logo.jpg');
        if (file_exists($logo)) {
            $pdf->Image($logo, 20, 15, 25);
        }
        $pdf->Cell(0, 5, 'DEPARTMENT OF SCIENCE AND TECHNOLOGY', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Cordillera Administrative Region Km.6,', 0, 1, 'C');
        $pdf->Cell(0, 5, 'La Trinidad, Benguet', 0, 1, 'C');

        $pdf->Ln(5);
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 7, 'NOTICE OF ALTERNATIVE MODE OF PROCUREMENT', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 5, "FUNDING SOURCE: GOVERNMENT OF THE PHILIPPINES THROUGH GENERAL APPROPRIATIONS ACT (GAA) FY " . $filterYear, 0, 'C');

        $pdf->Ln(3);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6,
            "The Department of Science and Technology (DOST-CAR), through its Bids and Awards Committee (BAC), invites bidders to apply for the eligibility and to bid for the following procurement activities on a per item basis. Bids received in excess of the Approved Budget for the Contract (ABC) for each of the following item listed below shall be automatically rejected at bid opening:",
            0, 'J');
        $pdf->Ln(5);

        // TABLE
        $html = '
        <table border="1" cellpadding="4" cellspacing="0">
            <thead>
                <tr style="background-color:#d9edf7; font-weight:bold; text-align:center;">
                    <th width="7%">Item No.</th>
                    <th width="20%">Purchase Request No.</th>
                    <th width="40%">Particulars</th>
                    <th width="18%">Total Approved Budget<br>(ABC)</th>
                    <th width="15%">Bid Submission Date</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($purchase as $i => $row) {
            $html .= '
                <tr>
                    <td width="7%">' . ($i + 1) . '</td>
                    <td width="20%">' . htmlspecialchars($row->pr_no) . '</td>
                    <td width="40%">' . nl2br(htmlspecialchars($row->purpose)) . '</td>
                    <td width="18%">' . number_format($row->total_cost, 2) . '</td>
                    <td width="15%">' . $row->created_date . '</td>
                </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('PurchaseRequestReport.pdf', 'I');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
