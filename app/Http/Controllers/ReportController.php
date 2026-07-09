<?php

namespace App\Http\Controllers;
use TCPDF;

use Illuminate\Http\Request;
use DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $purchase   = collect();
        $grandTotal = 0;
        $hasFilter  = false;
        $filterYear = now()->year;

        $dateRange  = $this->resolveDateRange($request);
        $dateFrom   = $dateRange[0];
        $dateTo     = $dateRange[1];

        if ($dateFrom || $dateTo) {
            $hasFilter  = true;
            $filterYear = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->year : now()->year;

            $query = DB::table('purchase_requests as pr')
                ->leftJoin('purchase_request_items as pri', 'pr.id', '=', 'pri.pr_id')
                ->select(
                    'pr.id',
                    'pr.pr_no',
                    'pr.purpose',
                    'pr.created_at',
                    DB::raw('COALESCE(SUM(pri.est_total_cost), 0) as total_cost')
                )
                ->groupBy('pr.id', 'pr.pr_no', 'pr.purpose', 'pr.created_at')
                ->orderBy('pr.created_at', 'desc');

            if ($dateFrom) {
                $query->whereDate('pr.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('pr.created_at', '<=', $dateTo);
            }

            $purchase   = $query->get();
            $grandTotal = $purchase->sum('total_cost');
        }

        return view(
            'modules.report.PurchaseRequestMonthlyReport.index',
            compact('purchase', 'grandTotal', 'hasFilter', 'filterYear')
        );
    }

    public function print(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $dateRange = $this->resolveDateRange($request);
        $dateFrom  = $dateRange[0];
        $dateTo    = $dateRange[1];

        $filterYear = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->year : now()->year;

        $query = DB::table('purchase_requests as pr')
            ->leftJoin('purchase_request_items as pri', 'pr.id', '=', 'pri.pr_id')
            ->select(
                'pr.pr_no',
                'pr.purpose',
                DB::raw('COALESCE(SUM(pri.est_total_cost), 0) as total_cost'),
                DB::raw('DATE_FORMAT(pr.created_at, "%M %d, %Y") as created_date')
            )
            ->groupBy('pr.id', 'pr.pr_no', 'pr.purpose', 'pr.created_at')
            ->orderBy('pr.created_at', 'desc');

        if ($dateFrom) {
            $query->whereDate('pr.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('pr.created_at', '<=', $dateTo);
        }

        $purchase = $query->get();

        // ── Build period label ─────────────────────────────────────────
        $period = $request->input('period', 'custom');

        if ($period === 'daily') {
            $dateRangeLabel = 'Today - ' . now()->format('F d, Y');

        } elseif ($period === 'weekly') {
            $dateRangeLabel = 'This Week - '
                . now()->startOfWeek()->format('M d')
                . ' to '
                . now()->copy()->endOfWeek()->format('M d, Y');

        } elseif ($period === 'monthly') {
            $month = $request->input('month');
            $year  = $request->input('year', now()->year);
            $dateRangeLabel = $month
                ? date('F', mktime(0, 0, 0, (int)$month, 1)) . ' ' . $year
                : 'All months of ' . $year;

        } elseif ($period === 'yearly') {
            $dateRangeLabel = 'Year ' . $request->input('year', now()->year);

        } else {
            $dateRangeLabel = $this->buildCustomLabel($dateFrom, $dateTo);
        }

        // ── PDF Setup ──────────────────────────────────────────────────
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetCreator('DOST-CAR');
        $pdf->SetAuthor('PFTMS');
        $pdf->AddPage();

        // ── HEADER: Logo absolutely positioned left, text centered on full page ──
        $logo = public_path('images/logo.jpg');
        if (file_exists($logo)) {
            $pdf->Image($logo, 15, 10, 20, 0, 'JPG');  // fixed position, does not affect cursor
        }

        // Set cursor to top and use full page width so text centers across entire page
        $pdf->SetY(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'DEPARTMENT OF SCIENCE AND TECHNOLOGY', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Cordillera Administrative Region Km.6,', 0, 1, 'C');
        $pdf->Cell(0, 5, 'La Trinidad, Benguet', 0, 1, 'C');

        // Ensure we clear the logo height before the title
        $pdf->SetY(max($pdf->GetY(), 25) + 4);

        // ── Title ──────────────────────────────────────────────────────
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 8, 'NOTICE OF ALTERNATIVE MODE OF PROCUREMENT', 0, 1, 'C');

        // ── Funding source (italic, centered) ─────────────────────────
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 5,
            'FUNDING SOURCE: GOVERNMENT OF THE PHILIPPINES THROUGH GENERAL APPROPRIATIONS ACT (GAA) FY ' . $filterYear,
            0, 'C');

        if ($dateRangeLabel) {
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->MultiCell(0, 5, 'Period Covered: ' . $dateRangeLabel, 0, 'C');
        }

        // ── Body paragraph ─────────────────────────────────────────────
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 5,
            'The Department of Science and Technology (DOST-CAR), through its Bids and Awards '
            . 'Committee (BAC), invites bidders to apply for the eligibility and to bid for the '
            . 'following procurement activities on a per item basis. Bids received in excess of '
            . 'the Approved Budget for the Contract (ABC) for each of the following item listed '
            . 'below shall be automatically rejected at bid opening:',
            0, 'J');
        $pdf->Ln(3);

        // ── Table header ───────────────────────────────────────────────
        // Column widths — total 180mm (A4 210mm - 15mm left - 15mm right)
        $w       = array(15, 35, 75, 32, 23);
        $headers = array(
            array('Item', 'No.'),
            array('Purchase Request', 'No.'),
            array('Particulars', ''),
            array('Total Approved', 'Budget (ABC)'),
            array('Bid', 'Submission Date'),
        );

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(217, 237, 247);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);

        // Draw multi-line header cells at equal height
        $headerH = 12; // fixed header row height
        $startX  = $pdf->GetX();
        $startY  = $pdf->GetY();

        foreach ($headers as $idx => $lines) {
            $text = implode("\n", array_filter($lines, function($l) { return $l !== ''; }));
            $pdf->MultiCell($w[$idx], $headerH, $text, 1, 'C', true, 0,
                $startX + array_sum(array_slice($w, 0, $idx)), $startY);
        }
        $pdf->SetY($startY + $headerH);

        // ── Table rows ─────────────────────────────────────────────────
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(249, 249, 249);
        $fill = false;

        foreach ($purchase as $i => $row) {
            $purposeLines = $pdf->getNumLines($row->purpose, $w[2]);
            $rowH = max(6, $purposeLines * 5);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // New page check
            if (($y + $rowH) > ($pdf->getPageHeight() - 20)) {
                $pdf->AddPage();

                // Reprint column headers on new page
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(217, 237, 247);
                $rpX = $pdf->GetX();
                $rpY = $pdf->GetY();
                foreach ($headers as $idx => $lines) {
                    $text = implode("\n", array_filter($lines, function($l) { return $l !== ''; }));
                    $pdf->MultiCell($w[$idx], $headerH, $text, 1, 'C', true, 0,
                        $rpX + array_sum(array_slice($w, 0, $idx)), $rpY);
                }
                $pdf->SetY($rpY + $headerH);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetFillColor(249, 249, 249);

                $x = $pdf->GetX();
                $y = $pdf->GetY();
            }

            $pdf->MultiCell($w[0], $rowH, ($i + 1),                          1, 'C', $fill, 0, $x,               $y);
            $pdf->MultiCell($w[1], $rowH, $row->pr_no,                        1, 'L', $fill, 0, $x + $w[0],       $y);
            $pdf->MultiCell($w[2], $rowH, $row->purpose,                      1, 'L', $fill, 0, $x + $w[0] + $w[1], $y);
            $pdf->MultiCell($w[3], $rowH, number_format($row->total_cost, 2), 1, 'R', $fill, 0, $x + $w[0] + $w[1] + $w[2], $y);
            $pdf->MultiCell($w[4], $rowH, $row->created_date,                 1, 'C', $fill, 1, $x + $w[0] + $w[1] + $w[2] + $w[3], $y);

            $fill = !$fill;
        }

        $pdf->Output('PurchaseRequestReport.pdf', 'I');
    }

    /* ─── HELPERS ──────────────────────────────────────────────────── */

    /**
     * Returns [dateFrom, dateTo] as Y-m-d strings or null.
     */
    private function resolveDateRange(Request $request)
    {
        $period = $request->input('period', 'custom');

        if ($period === 'daily') {
            return array(now()->toDateString(), now()->toDateString());

        } elseif ($period === 'weekly') {
            return array(
                now()->startOfWeek()->toDateString(),
                now()->copy()->endOfWeek()->toDateString()
            );

        } elseif ($period === 'monthly') {
            $month = $request->input('month');
            $year  = $request->input('year');

            if (!$month && !$year) {
                return array(null, null);
            }

            $year = $year ? (int)$year : now()->year;

            if ($month) {
                $start = \Carbon\Carbon::createFromDate($year, (int)$month, 1)->startOfMonth()->toDateString();
                $end   = \Carbon\Carbon::createFromDate($year, (int)$month, 1)->endOfMonth()->toDateString();
            } else {
                $start = \Carbon\Carbon::createFromDate($year, 1,  1)->startOfYear()->toDateString();
                $end   = \Carbon\Carbon::createFromDate($year, 12, 31)->endOfYear()->toDateString();
            }

            return array($start, $end);

        } elseif ($period === 'yearly') {
            $year = $request->input('year');

            if (!$year) {
                return array(null, null);
            }

            $year = (int)$year;
            return array(
                \Carbon\Carbon::createFromDate($year, 1,  1)->startOfYear()->toDateString(),
                \Carbon\Carbon::createFromDate($year, 12, 31)->endOfYear()->toDateString()
            );

        } else {
            // Custom date range
            return array(
                $request->filled('date_from') ? $request->input('date_from') : null,
                $request->filled('date_to')   ? $request->input('date_to')   : null
            );
        }
    }

    private function buildCustomLabel($from, $to)
    {
        if ($from && $to) {
            return \Carbon\Carbon::parse($from)->format('F d, Y')
                 . ' to '
                 . \Carbon\Carbon::parse($to)->format('F d, Y');
        }
        if ($from) {
            return 'From ' . \Carbon\Carbon::parse($from)->format('F d, Y');
        }
        if ($to) {
            return 'Up to ' . \Carbon\Carbon::parse($to)->format('F d, Y');
        }
        return '';
    }

    public function create() {}
    public function store(Request $request) {}
    public function show($id) {}
    public function edit($id) {}
    public function update(Request $request, $id) {}
    public function destroy($id) {}
}
