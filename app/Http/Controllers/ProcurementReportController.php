<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcurementReportController extends Controller
{
    // ============================================================
    // INDEX
    // ============================================================
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

    // ============================================================
    // GENERATE
    // ============================================================
    public function generate(Request $request)
    {
        $filterMode = $request->input('filter_mode', 'monthly');
        $month = $year = $yearOnly = $dateFrom = $dateTo = null;

        if ($filterMode === 'monthly') {
            $month = $request->input('month');
            $year  = $request->input('year');
            if (!$year) return redirect()->route('procurement.report.index')
                ->with('error', 'Please select a year.');
        } elseif ($filterMode === 'yearly') {
            $yearOnly = $request->input('year_only');
            $year     = $yearOnly;
            if (!$year) return redirect()->route('procurement.report.index')
                ->with('error', 'Please select a year.');
        } elseif ($filterMode === 'custom') {
            $dateFrom = $request->input('date_from');
            $dateTo   = $request->input('date_to');
            if (!$dateFrom || !$dateTo) return redirect()->route('procurement.report.index')
                ->with('error', 'Please select both Date From and Date To.');
            if ($dateFrom > $dateTo) return redirect()->route('procurement.report.index')
                ->with('error', 'Date From must not be later than Date To.');
        }

        $reportData = $this->getProcurementData($month, $year, $dateFrom, $dateTo);

        $periods = DB::table('purchase_requests')
            ->selectRaw('YEAR(date_pr) as year, MONTH(date_pr) as month')
            ->whereNotNull('date_pr')->distinct()
            ->orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        return view('modules.procurement.report', compact(
            'reportData', 'periods', 'filterMode', 'month', 'year', 'yearOnly', 'dateFrom', 'dateTo'
        ));
    }

    // ============================================================
    // EXPORT CSV
    // ============================================================
    public function exportCsv(Request $request)
    {
        $filterMode = $request->input('filter_mode', 'monthly');
        $month      = $request->input('month');
        $year       = $request->input('year') ?: $request->input('year_only');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');

        $reportData = $this->getProcurementData($month, $year, $dateFrom, $dateTo);

        if ($filterMode === 'custom' && $dateFrom && $dateTo) {
            $periodLabel = date('F d, Y', strtotime($dateFrom)) . ' to ' . date('F d, Y', strtotime($dateTo));
        } elseif ($month && $year) {
            $periodLabel = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        } else {
            $periodLabel = 'Full Year ' . $year;
        }

        $filename = 'PMR_' . ($year ?? 'custom') . '_'
            . ($month ? str_pad($month, 2, '0', STR_PAD_LEFT) : 'FullYear')
            . '_' . date('YmdHis') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($reportData, $periodLabel) {
            $file  = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            $blank = function () use ($file) { fputcsv($file, []); };

            $totalABC      = $reportData->sum('total_abc');
            $totalMOOE     = $reportData->sum('total_mooe');
            $totalCO       = $reportData->sum('total_co');
            $totalContract = $reportData->sum('contract_total');
            $totalConMOOE  = $reportData->sum('contract_mooe');
            $totalConCO    = $reportData->sum('contract_co');
            $totalSavings  = $totalABC - $totalContract;
            $totalCount    = count($reportData);

            // Header
            fputcsv($file, ['DEPARTMENT OF SCIENCE AND TECHNOLOGY']);
            fputcsv($file, ['CORDILLERA ADMINISTRATIVE REGION']);
            fputcsv($file, ['Procurement Monitoring Report']);
            fputcsv($file, ['Period:', $periodLabel]);
            fputcsv($file, ['Generated:', date('F d, Y  h:i A')]);
            fputcsv($file, ['Total Records:', $totalCount]);
            $blank();

            // Summary
            fputcsv($file, ['--- SUMMARY ---']);
            fputcsv($file, ['', 'Total (PHP)', 'MOOE (PHP)', 'CO (PHP)']);
            fputcsv($file, ['ABC (Approved Budget)', number_format($totalABC, 2, '.', ','), number_format($totalMOOE, 2, '.', ','), number_format($totalCO, 2, '.', ',')]);
            fputcsv($file, ['Contract Cost', number_format($totalContract, 2, '.', ','), number_format($totalConMOOE, 2, '.', ','), number_format($totalConCO, 2, '.', ',')]);
            fputcsv($file, ['Savings (ABC - Contract)', number_format($totalSavings, 2, '.', ','), '', '']);
            $blank(); $blank();

            // Column headers
            $colHeaders = [
                '#', 'PR No.', 'Code (PAP)', 'Procurement Project', 'PMO / End-User',
                'Is this an Early Procurement Activity?', 'Mode of Procurement',
                'Pre-Proc Conference', 'Ads / Post of IB', 'Pre-bid Conference',
                'Eligibility Check', 'Sub / Open of Bids', 'Bid Evaluation',
                'Post Qualification', 'Date of BAC Resolution Recommending Award',
                'Notice of Award', 'Contract Signing', 'Notice to Proceed',
                'Delivery / Completion', 'Inspection & Acceptance',
                'Source of Funds',
                'ABC Total', 'ABC MOOE', 'ABC CO',
                'Contract Total', 'Contract MOOE', 'Contract CO',
                'Pre-bid Conf (Observers)', 'Eligibility Check (Observers)',
                'Sub/Open of Bids (Observers)', 'Bid Evaluation (Observers)',
                'Post Qual (Observers)',
                'Delivery/Completion/Acceptance (If applicable)',
                'Remarks (Explaining changes from the APP)',
            ];

            // All procurement activities
            fputcsv($file, ['--- PROCUREMENT ACTIVITIES (' . $totalCount . ' records) ---']);
            $blank();
            fputcsv($file, $colHeaders);

            $rowNum = 1;
            foreach ($reportData as $item) {
                $savings = ($item->total_abc ?? 0) - ($item->contract_total ?? 0);
                fputcsv($file, [
                    $rowNum++,
                    $item->pr_no                 ?? '',
                    '',
                    $item->purpose               ?? '',
                    $item->division_name         ?? '',
                    'No',
                    $item->mode_procurement_name ?? '',
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_abstract_approved),
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_canvass),
                    $this->formatDate($item->date_iar),
                    $this->formatDate($item->date_received),
                    "Gov't of the Philippines (current year's budget)",
                    number_format($item->total_abc      ?? 0, 2, '.', ','),
                    number_format($item->total_mooe     ?? 0, 2, '.', ','),
                    number_format($item->total_co       ?? 0, 2, '.', ','),
                    number_format($item->contract_total ?? 0, 2, '.', ','),
                    number_format($item->contract_mooe  ?? 0, 2, '.', ','),
                    number_format($item->contract_co    ?? 0, 2, '.', ','),
                    '', '', '', '', '', '', '',
                ]);
            }

            $blank();
            // Sub-total
            fputcsv($file, [
                '', 'TOTAL (' . $totalCount . ' records)',
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
                number_format($totalABC,      2, '.', ','),
                number_format($totalMOOE,     2, '.', ','),
                number_format($totalCO,       2, '.', ','),
                number_format($totalContract, 2, '.', ','),
                number_format($totalConMOOE,  2, '.', ','),
                number_format($totalConCO,    2, '.', ','),
                '', '', '', '', '', '', '',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ============================================================
    // EXPORT EXCEL  — native .xlsx via ZipArchive (no warnings)
    // ============================================================
    public function exportExcel(Request $request)
    {
        $filterMode = $request->input('filter_mode', 'monthly');
        $month      = $request->input('month');
        $year       = $request->input('year') ?: $request->input('year_only');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');

        $reportData = $this->getProcurementData($month, $year, $dateFrom, $dateTo);

        if ($filterMode === 'custom' && $dateFrom && $dateTo) {
            $periodLabel = date('F d, Y', strtotime($dateFrom)) . ' to ' . date('F d, Y', strtotime($dateTo));
        } elseif ($month && $year) {
            $periodLabel = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        } else {
            $periodLabel = 'Full Year ' . $year;
        }

        $filename = 'PMR_' . ($year ?? 'custom') . '_'
            . ($month ? str_pad($month, 2, '0', STR_PAD_LEFT) : 'FullYear')
            . '_' . date('YmdHis') . '.xlsx';

        $tmpFile = $this->buildXlsx($reportData, $periodLabel);

        return response()->download($tmpFile, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ])->deleteFileAfterSend(true);
    }

    // ============================================================
    // PRIVATE – convert column number (1-based) to Excel letter(s)
    // e.g. 1=A, 26=Z, 27=AA, 37=AK
    // ============================================================
    private function colLetter(int $n): string
    {
        $r = '';
        while ($n > 0) {
            $n--;
            $r = chr(65 + ($n % 26)) . $r;
            $n = (int)($n / 26);
        }
        return $r;
    }

    // ============================================================
    // PRIVATE – build native .xlsx using ZipArchive (PHP built-in)
    // Two sheets: Summary | Procurement Activities
    // ============================================================
    private function buildXlsx($reportData, $periodLabel)
    {
        // ── Shared string registry ───────────────────────────────
        $strings  = [];
        $strIndex = [];
        $si = function ($str) use (&$strings, &$strIndex) {
            $key = (string)$str;
            if (!isset($strIndex[$key])) {
                $strIndex[$key] = count($strings);
                $strings[]      = $key;
            }
            return $strIndex[$key];
        };

        // ── Cell helpers ─────────────────────────────────────────
        // String cell (references shared string index)
        $cs = function ($colNum, $row, $style, $strIdx) {
            $col = $this->colLetter($colNum);
            return '<c r="' . $col . $row . '" s="' . $style . '" t="s"><v>' . $strIdx . '</v></c>';
        };
        // Number cell
        $cn = function ($colNum, $row, $style, $value) {
            $col = $this->colLetter($colNum);
            return '<c r="' . $col . $row . '" s="' . $style . '"><v>' . (float)$value . '</v></c>';
        };

        // ── Totals ───────────────────────────────────────────────
        $totalABC      = (float)$reportData->sum('total_abc');
        $totalMOOE     = (float)$reportData->sum('total_mooe');
        $totalCO       = (float)$reportData->sum('total_co');
        $totalContract = (float)$reportData->sum('contract_total');
        $totalConMOOE  = (float)$reportData->sum('contract_mooe');
        $totalConCO    = (float)$reportData->sum('contract_co');
        $totalSavings  = $totalABC - $totalContract;

        // ── Style indices ────────────────────────────────────────
        // These must match the order in cellXfs in styles.xml below
        $S_DEFAULT     = 0;
        $S_TITLE_MAIN  = 1;  // dark navy, white bold 15pt, center
        $S_TITLE_SUB   = 2;  // medium blue, white bold 12pt, center
        $S_PERIOD      = 3;  // light blue bg, navy bold 11pt, center
        $S_SEC_BLUE    = 4;  // dark navy section banner
        $S_COL_HDR1    = 5;  // col header group row – dark blue bg
        $S_COL_HDR2    = 6;  // col header sub row – slightly lighter blue
        $S_DAT         = 7;  // white row
        $S_DAT_ALT     = 8;  // light blue alt row
        $S_NUM         = 9;  // white row, right-aligned, #,##0.00
        $S_NUM_ALT     = 10; // alt row, right-aligned, #,##0.00
        $S_DATE        = 11; // white row, center
        $S_DATE_ALT    = 12; // alt row, center
        $S_ROWNUM      = 13; // row number, bold, center, left border
        $S_ROWNUM_ALT  = 14;
        $S_SUBTOT_LBL  = 15; // sub-total label, blue bg
        $S_SUBTOT      = 16; // sub-total number, blue bg
        $S_SUM_HDR     = 17; // summary sheet header
        $S_SUM_LBL     = 18; // summary label cell
        $S_SUM_VAL     = 19; // summary value cell
        $S_SUM_GREEN   = 20; // summary green value
        $S_SUM_SAV_LBL = 21; // savings label – yellow
        $S_SUM_SAV     = 22; // savings value – yellow
        $S_GRAND_LBL   = 23; // grand total label – dark navy
        $S_GRAND       = 24; // grand total number – dark navy

        // ── styles.xml ───────────────────────────────────────────
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="9">
    <font><sz val="11"/><name val="Calibri"/></font>
    <font><sz val="15"/><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><sz val="12"/><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><sz val="11"/><b/><color rgb="FF1F3864"/><name val="Calibri"/></font>
    <font><sz val="10"/><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><sz val="9"/><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    <font><sz val="9"/><name val="Calibri"/></font>
    <font><sz val="9"/><b/><color rgb="FF1F3864"/><name val="Calibri"/></font>
    <font><sz val="10"/><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
  </fonts>
  <fills count="12">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1F3864"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF2E5EA8"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFD6E4F0"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF4472C4"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF5B9BD5"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDEEAF1"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFBDD7EE"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFE2EFDA"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFF2CC"/></patternFill></fill>
  </fills>
  <borders count="4">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFBDD7EE"/></left>
      <right style="thin"><color rgb="FFBDD7EE"/></right>
      <top style="thin"><color rgb="FFBDD7EE"/></top>
      <bottom style="thin"><color rgb="FFBDD7EE"/></bottom>
      <diagonal/>
    </border>
    <border>
      <left style="medium"><color rgb="FF2E5EA8"/></left>
      <right style="medium"><color rgb="FF2E5EA8"/></right>
      <top style="medium"><color rgb="FF1F3864"/></top>
      <bottom style="medium"><color rgb="FF1F3864"/></bottom>
      <diagonal/>
    </border>
    <border>
      <left style="medium"><color rgb="FF2E5EA8"/></left>
      <right style="medium"><color rgb="FF2E5EA8"/></right>
      <top style="medium"><color rgb="FF2E5EA8"/></top>
      <bottom style="medium"><color rgb="FF2E5EA8"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="25">
    <!-- 0: default -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <!-- 1: title main -->
    <xf numFmtId="0" fontId="1" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <!-- 2: title sub -->
    <xf numFmtId="0" fontId="2" fillId="3" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
    <!-- 3: period row -->
    <xf numFmtId="0" fontId="3" fillId="4" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <!-- 4: section banner -->
    <xf numFmtId="0" fontId="4" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="left" vertical="center"/>
    </xf>
    <!-- 5: col header group (dark blue) -->
    <xf numFmtId="0" fontId="5" fillId="5" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <!-- 6: col header sub (lighter blue) -->
    <xf numFmtId="0" fontId="5" fillId="6" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <!-- 7: data cell white -->
    <xf numFmtId="0" fontId="6" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment vertical="center" wrapText="1"/>
    </xf>
    <!-- 8: data cell alt (light blue) -->
    <xf numFmtId="0" fontId="6" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment vertical="center" wrapText="1"/>
    </xf>
    <!-- 9: number white -->
    <xf numFmtId="4" fontId="6" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
    <!-- 10: number alt -->
    <xf numFmtId="4" fontId="6" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
    <!-- 11: date center white -->
    <xf numFmtId="0" fontId="6" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
    <!-- 12: date center alt -->
    <xf numFmtId="0" fontId="6" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
    <!-- 13: row number white -->
    <xf numFmtId="0" fontId="7" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
    <!-- 14: row number alt -->
    <xf numFmtId="0" fontId="7" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
    <!-- 15: sub-total label -->
    <xf numFmtId="0" fontId="7" fillId="9" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="left" vertical="center"/>
    </xf>
    <!-- 16: sub-total number -->
    <xf numFmtId="4" fontId="7" fillId="9" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
    <!-- 17: summary header -->
    <xf numFmtId="0" fontId="5" fillId="5" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
    <!-- 18: summary label -->
    <xf numFmtId="0" fontId="7" fillId="4" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="left" vertical="center"/>
    </xf>
    <!-- 19: summary value -->
    <xf numFmtId="4" fontId="7" fillId="7" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
    <!-- 20: summary green value -->
    <xf numFmtId="4" fontId="7" fillId="10" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
    <!-- 21: savings label yellow -->
    <xf numFmtId="0" fontId="7" fillId="11" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="left" vertical="center"/>
    </xf>
    <!-- 22: savings value yellow -->
    <xf numFmtId="4" fontId="7" fillId="11" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
    <!-- 23: grand total label -->
    <xf numFmtId="0" fontId="8" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="left" vertical="center"/>
    </xf>
    <!-- 24: grand total number -->
    <xf numFmtId="4" fontId="8" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
      <alignment horizontal="right" vertical="center"/>
    </xf>
  </cellXfs>
</styleSheet>';

        // ────────────────────────────────────────────────────────
        // COLUMN MAP for the Procurement sheet
        // We use 1-based integers throughout to avoid PHP 8 $col++
        // ────────────────────────────────────────────────────────
        // A=1   #
        // B=2   PR No.
        // C=3   Code (PAP)
        // D=4   Procurement Project
        // E=5   PMO/End-User
        // F=6   Early Procurement?
        // G=7   Mode of Procurement
        // H=8   Pre-Proc Conference
        // I=9   Ads/Post of IB
        // J=10  Pre-bid Conf
        // K=11  Eligibility Check
        // L=12  Sub/Open of Bids
        // M=13  Bid Evaluation
        // N=14  Post Qual
        // O=15  BAC Resolution
        // P=16  Notice of Award
        // Q=17  Contract Signing
        // R=18  Notice to Proceed
        // S=19  Delivery/Completion
        // T=20  Inspection & Acceptance
        // U=21  Source of Funds
        // V=22  ABC Total
        // W=23  ABC MOOE
        // X=24  ABC CO
        // Y=25  Contract Total
        // Z=26  Contract MOOE
        // AA=27 Contract CO
        // AB=28 Pre-bid Conf (Observers)
        // AC=29 Eligibility Check (Observers)
        // AD=30 Sub/Open of Bids (Observers)
        // AE=31 Bid Evaluation (Observers)
        // AF=32 Post Qual (Observers)
        // AG=33 Delivery/Completion/Acceptance
        // AH=34 Remarks
        // AI=35 Savings
        // AJ=36 Supplier
        // AH=34 Remarks  (AI Savings, AJ Supplier, AK Status REMOVED)
        $TOTAL_COLS = 34;
        $LAST_COL   = $this->colLetter($TOTAL_COLS); // "AH"

        // ── BUILD PROCUREMENT SHEET ──────────────────────────────
        $rows   = '';
        $merges = [];
        $r      = 1;

        // Title rows
        $rows .= '<row r="' . $r . '" ht="30" customHeight="1">';
        $rows .= $cs(1, $r, $S_TITLE_MAIN, $si('DEPARTMENT OF SCIENCE AND TECHNOLOGY'));
        $rows .= '</row>';
        $merges[] = 'A' . $r . ':' . $LAST_COL . $r; $r++;

        $rows .= '<row r="' . $r . '" ht="22" customHeight="1">';
        $rows .= $cs(1, $r, $S_TITLE_SUB, $si('Procurement Monitoring Report'));
        $rows .= '</row>';
        $merges[] = 'A' . $r . ':' . $LAST_COL . $r; $r++;

        $rows .= '<row r="' . $r . '" ht="20" customHeight="1">';
        $rows .= $cs(1, $r, $S_PERIOD, $si('Period: ' . $periodLabel . '   |   CORDILLERA ADMINISTRATIVE REGION   |   Generated: ' . date('F d, Y  h:i A')));
        $rows .= '</row>';
        $merges[] = 'A' . $r . ':' . $LAST_COL . $r; $r++;

        // Blank
        $rows .= '<row r="' . $r . '" ht="6" customHeight="1"></row>'; $r++;

        // Section banner
        $rows .= '<row r="' . $r . '" ht="24" customHeight="1">';
        $rows .= $cs(1, $r, $S_SEC_BLUE, $si('  PROCUREMENT ACTIVITIES '));
        $rows .= '</row>';
        $merges[] = 'A' . $r . ':' . $LAST_COL . $r; $r++;

        $rows .= '<row r="' . $r . '" ht="5" customHeight="1"></row>'; $r++;

        // ── Column header row 1 (group labels with merges) ───────
        $rows .= '<row r="' . $r . '" ht="50" customHeight="1">';
        // Single-cell headers that span both header rows (MergeDown=1 handled via merges)
        foreach ([1,2,3,4,5,6,7] as $c) {
            $labels = ['#','PR No.','Code (PAP)','Procurement Project','PMO / End-User','Is this an Early Procurement Activity?','Mode of Procurement'];
            $rows .= $cs($c, $r, $S_COL_HDR1, $si($labels[$c-1]));
        }
        // Grouped: Actual Procurement Activities H(8)..T(20)
        $rows .= $cs(8, $r, $S_COL_HDR1, $si('Actual Procurement Activities'));
        // Source of Funds U(21)
        $rows .= $cs(21, $r, $S_COL_HDR1, $si('Source of Funds'));
        // ABC V(22)..X(24)
        $rows .= $cs(22, $r, $S_COL_HDR1, $si('ABC (PhP)'));
        // Contract Y(25)..AA(27)
        $rows .= $cs(25, $r, $S_COL_HDR1, $si('Contract Cost (PhP)'));
        // Date of Receipt of Invitation AB(28)..AH(34)
        $rows .= $cs(28, $r, $S_COL_HDR1, $si('Date of Receipt of Invitation'));
        $rows .= '</row>';
        // Merges for row 1 header
        $merges[] = 'A' . $r . ':A' . ($r+1);
        $merges[] = 'B' . $r . ':B' . ($r+1);
        $merges[] = 'C' . $r . ':C' . ($r+1);
        $merges[] = 'D' . $r . ':D' . ($r+1);
        $merges[] = 'E' . $r . ':E' . ($r+1);
        $merges[] = 'F' . $r . ':F' . ($r+1);
        $merges[] = 'G' . $r . ':G' . ($r+1);
        $merges[] = 'H' . $r . ':T' . $r;          // 13 activity cols
        $merges[] = 'U' . $r . ':U' . ($r+1);
        $merges[] = 'V' . $r . ':X' . $r;           // ABC 3 cols
        $merges[] = 'Y' . $r . ':AA' . $r;          // Contract 3 cols
        $merges[] = 'AB' . $r . ':AH' . $r;         // 7 observer/remarks cols
        $r++;

        // ── Column header row 2 (sub-labels) ─────────────────────
        $rows .= '<row r="' . $r . '" ht="45" customHeight="1">';
        // A..G are merged with row above so they get empty cells
        for ($c = 1; $c <= 7; $c++) {
            $rows .= $cs($c, $r, $S_COL_HDR2, $si(''));
        }
        // H..T – 13 activity sub-labels
        $actLabels = [
            8  => 'Pre-Proc Conference',
            9  => 'Ads/Post of IB',
            10 => 'Pre-bid Conf',
            11 => 'Eligibility Check',
            12 => 'Sub/Open of Bids',
            13 => 'Bid Evaluation',
            14 => 'Post Qual',
            15 => 'Date of BAC Resolution Recommending Award',
            16 => 'Notice of Award',
            17 => 'Contract Signing',
            18 => 'Notice to Proceed',
            19 => 'Delivery/Completion',
            20 => 'Inspection & Acceptance',
        ];
        foreach ($actLabels as $c => $label) {
            $rows .= $cs($c, $r, $S_COL_HDR2, $si($label));
        }
        // U merged above
        $rows .= $cs(21, $r, $S_COL_HDR2, $si(''));
        // ABC sub
        $rows .= $cs(22, $r, $S_COL_HDR2, $si('Total'));
        $rows .= $cs(23, $r, $S_COL_HDR2, $si('MOOE'));
        $rows .= $cs(24, $r, $S_COL_HDR2, $si('CO'));
        // Contract sub
        $rows .= $cs(25, $r, $S_COL_HDR2, $si('Total'));
        $rows .= $cs(26, $r, $S_COL_HDR2, $si('MOOE'));
        $rows .= $cs(27, $r, $S_COL_HDR2, $si('CO'));
        // Observer/remarks sub AB..AH
        $obsLabels = [
            28 => 'Pre-bid Conf',
            29 => 'Eligibility Check',
            30 => 'Sub/Open of Bids',
            31 => 'Bid Evaluation',
            32 => 'Post Qual',
            33 => 'Delivery/Completion/Acceptance (If applicable)',
            34 => 'Remarks (Explaining changes from the APP)',
        ];
        foreach ($obsLabels as $c => $label) {
            $rows .= $cs($c, $r, $S_COL_HDR2, $si($label));
        }
        // AI, AJ, AK removed
        $rows .= '</row>';
        $r++;

        // ── Data rows ────────────────────────────────────────────
        $rowNum = 1;
        foreach ($reportData as $item) {
            $alt     = ($rowNum % 2 === 0);
            $ds      = $alt ? $S_DAT_ALT     : $S_DAT;
            $ns      = $alt ? $S_NUM_ALT     : $S_NUM;
            $dts     = $alt ? $S_DATE_ALT    : $S_DATE;
            $rns     = $alt ? $S_ROWNUM_ALT  : $S_ROWNUM;
            $savings = (float)($item->total_abc ?? 0) - (float)($item->contract_total ?? 0);

            $rows .= '<row r="' . $r . '" ht="20" customHeight="1">';
            $rows .= $cn(1,  $r, $rns, $rowNum);
            $rows .= $cs(2,  $r, $ds,  $si($item->pr_no ?? ''));
            $rows .= $cs(3,  $r, $ds,  $si(''));
            $rows .= $cs(4,  $r, $ds,  $si($item->purpose ?? ''));
            $rows .= $cs(5,  $r, $ds,  $si($item->division_name ?? ''));
            $rows .= $cs(6,  $r, $ds,  $si('No'));
            $rows .= $cs(7,  $r, $ds,  $si($item->mode_procurement_name ?? ''));
            // 13 activity date columns (H..T = col 8..20)
            $rows .= $cs(8,  $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(9,  $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(10, $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(11, $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(12, $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(13, $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(14, $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(15, $r, $dts, $si($this->formatDate($item->date_abstract_approved)));
            $rows .= $cs(16, $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(17, $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(18, $r, $dts, $si($this->formatDate($item->date_canvass)));
            $rows .= $cs(19, $r, $dts, $si($this->formatDate($item->date_iar)));
            $rows .= $cs(20, $r, $dts, $si($this->formatDate($item->date_received)));
            // Source of funds U=21
            $rows .= $cs(21, $r, $ds,  $si("Gov't of the Philippines (current year's budget)"));
            // ABC V..X = 22..24
            $rows .= $cn(22, $r, $ns, (float)($item->total_abc      ?? 0));
            $rows .= $cn(23, $r, $ns, (float)($item->total_mooe     ?? 0));
            $rows .= $cn(24, $r, $ns, (float)($item->total_co       ?? 0));
            // Contract Y..AA = 25..27
            $rows .= $cn(25, $r, $ns, (float)($item->contract_total ?? 0));
            $rows .= $cn(26, $r, $ns, (float)($item->contract_mooe  ?? 0));
            $rows .= $cn(27, $r, $ns, (float)($item->contract_co    ?? 0));
            // Observers AB..AH = 28..34 (empty for now)
            for ($c = 28; $c <= 34; $c++) {
                $rows .= $cs($c, $r, $ds, $si(''));
            }
            // Savings AI=35, Supplier AJ=36, Status AK=37 REMOVED
            $rows .= '</row>';
            $r++;
            $rowNum++;
        }

        // Sub-total row
        $rows .= '<row r="' . $r . '" ht="22" customHeight="1">';
        $rows .= $cs(1, $r, $S_SUBTOT_LBL, $si('TOTAL (' . $reportData->count() . ' records)'));
        $merges[] = 'A' . $r . ':U' . $r;
        $rows .= $cn(22, $r, $S_SUBTOT, $totalABC);
        $rows .= $cn(23, $r, $S_SUBTOT, $totalMOOE);
        $rows .= $cn(24, $r, $S_SUBTOT, $totalCO);
        $rows .= $cn(25, $r, $S_SUBTOT, $totalContract);
        $rows .= $cn(26, $r, $S_SUBTOT, $totalConMOOE);
        $rows .= $cn(27, $r, $S_SUBTOT, $totalConCO);
        for ($c = 28; $c <= 34; $c++) {
            $rows .= $cs($c, $r, $S_SUBTOT_LBL, $si(''));
        }
        // AI Savings, AJ Supplier, AK Status REMOVED
        $rows .= '</row>';
        $r++;

        // ── Column widths for procurement sheet ──────────────────
        $dataCols = '<cols>'
            . '<col min="1"  max="1"  width="5"   customWidth="1"/>'  // #
            . '<col min="2"  max="2"  width="12"  customWidth="1"/>'  // PR No
            . '<col min="3"  max="3"  width="8"   customWidth="1"/>'  // Code
            . '<col min="4"  max="4"  width="32"  customWidth="1"/>'  // Purpose
            . '<col min="5"  max="5"  width="20"  customWidth="1"/>'  // PMO
            . '<col min="6"  max="6"  width="8"   customWidth="1"/>'  // Early
            . '<col min="7"  max="7"  width="18"  customWidth="1"/>'  // Mode
            . '<col min="8"  max="20" width="11"  customWidth="1"/>'  // 13 activity cols
            . '<col min="21" max="21" width="22"  customWidth="1"/>'  // Source of funds
            . '<col min="22" max="27" width="14"  customWidth="1"/>'  // ABC + Contract 6 cols
            . '<col min="28" max="34" width="14"  customWidth="1"/>'  // Observer/Remarks 7 cols
            . '</cols>';

        // Wrap sheet XML
        $buildMergeXml = function ($merges) {
            if (empty($merges)) return '';
            $xml = '<mergeCells count="' . count($merges) . '">';
            foreach ($merges as $m) { $xml .= '<mergeCell ref="' . $m . '"/>'; }
            return $xml . '</mergeCells>';
        };

        $procSheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $dataCols
            . '<sheetData>' . $rows . '</sheetData>'
            . $buildMergeXml($merges)
            . '</worksheet>';

        // ── BUILD SUMMARY SHEET ───────────────────────────────────
        $sRows   = '';
        $sMerges = [];
        $sr      = 1;
        $SLAST   = 'D';

        $sRows .= '<row r="' . $sr . '" ht="35" customHeight="1">';
        $sRows .= $cs(1, $sr, $S_TITLE_MAIN, $si('DEPARTMENT OF SCIENCE AND TECHNOLOGY'));
        $sRows .= '</row>';
        $sMerges[] = 'A' . $sr . ':' . $SLAST . $sr; $sr++;

        $sRows .= '<row r="' . $sr . '" ht="22" customHeight="1">';
        $sRows .= $cs(1, $sr, $S_TITLE_SUB, $si('Procurement Monitoring Report — Summary'));
        $sRows .= '</row>';
        $sMerges[] = 'A' . $sr . ':' . $SLAST . $sr; $sr++;

        $sRows .= '<row r="' . $sr . '" ht="20" customHeight="1">';
        $sRows .= $cs(1, $sr, $S_PERIOD, $si('Period: ' . $periodLabel . '   |   Generated: ' . date('F d, Y  h:i A')));
        $sRows .= '</row>';
        $sMerges[] = 'A' . $sr . ':' . $SLAST . $sr; $sr++;

        $sRows .= '<row r="' . $sr . '" ht="8" customHeight="1"></row>'; $sr++;

        // Header row
        $sRows .= '<row r="' . $sr . '" ht="22" customHeight="1">';
        foreach ([1=>'Category', 2=>'Total (PHP)', 3=>'MOOE (PHP)', 4=>'CO (PHP)'] as $c => $lbl) {
            $sRows .= $cs($c, $sr, $S_SUM_HDR, $si($lbl));
        }
        $sRows .= '</row>'; $sr++;

        // ABC row
        $sRows .= '<row r="' . $sr . '" ht="22" customHeight="1">';
        $sRows .= $cs(1, $sr, $S_SUM_LBL, $si('ABC (Approved Budget)'));
        $sRows .= $cn(2, $sr, $S_SUM_VAL, $totalABC);
        $sRows .= $cn(3, $sr, $S_SUM_VAL, $totalMOOE);
        $sRows .= $cn(4, $sr, $S_SUM_VAL, $totalCO);
        $sRows .= '</row>'; $sr++;

        // Contract row
        $sRows .= '<row r="' . $sr . '" ht="22" customHeight="1">';
        $sRows .= $cs(1, $sr, $S_SUM_LBL, $si('Contract Cost'));
        $sRows .= $cn(2, $sr, $S_SUM_GREEN, $totalContract);
        $sRows .= $cn(3, $sr, $S_SUM_GREEN, $totalConMOOE);
        $sRows .= $cn(4, $sr, $S_SUM_GREEN, $totalConCO);
        $sRows .= '</row>'; $sr++;

        // Savings row
        $sRows .= '<row r="' . $sr . '" ht="22" customHeight="1">';
        $sRows .= $cs(1, $sr, $S_SUM_SAV_LBL, $si('Savings (ABC - Contract)'));
        $sRows .= $cn(2, $sr, $S_SUM_SAV, $totalSavings);
        $sRows .= $cs(3, $sr, $S_SUM_SAV, $si(''));
        $sRows .= $cs(4, $sr, $S_SUM_SAV, $si(''));
        $sRows .= '</row>'; $sr++;

        $sRows .= '<row r="' . $sr . '" ht="8" customHeight="1"></row>'; $sr++;

        // Grand total
        $sRows .= '<row r="' . $sr . '" ht="24" customHeight="1">';
        $sRows .= $cs(1, $sr, $S_GRAND_LBL, $si('GRAND TOTAL (' . $reportData->count() . ' records)'));
        $sRows .= $cn(2, $sr, $S_GRAND, $totalABC);
        $sRows .= $cn(3, $sr, $S_GRAND, $totalContract);
        $sRows .= $cn(4, $sr, $S_GRAND, $totalSavings);
        $sRows .= '</row>'; $sr++;

        $sumCols = '<cols>'
            . '<col min="1" max="1" width="32" customWidth="1"/>'
            . '<col min="2" max="4" width="22" customWidth="1"/>'
            . '</cols>';

        $summarySheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $sumCols
            . '<sheetData>' . $sRows . '</sheetData>'
            . $buildMergeXml($sMerges)
            . '</worksheet>';

        // ── Shared strings XML ────────────────────────────────────
        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
        foreach ($strings as $s) {
            $ssXml .= '<si><t xml:space="preserve">'
                . htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                . '</t></si>';
        }
        $ssXml .= '</sst>';

        // ── Workbook XML  (2 sheets: Summary + Procurement) ───────
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            // . '<sheet name="Summary"               sheetId="1" r:id="rId1"/>'
            . '<sheet name="Procurement Activities" sheetId="2" r:id="rId2"/>'
            . '</sheets>'
            . '</workbook>';

        // ── Relationships ─────────────────────────────────────────
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml"  ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml"          ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml"     ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/xl/styles.xml"            ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        // ── Assemble .xlsx ────────────────────────────────────────
        $tmpFile = tempnam(sys_get_temp_dir(), 'pmr_') . '.xlsx';
        $zip     = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',        $contentTypes);
        $zip->addFromString('_rels/.rels',                $rootRels);
        $zip->addFromString('xl/workbook.xml',            $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/styles.xml',              $stylesXml);
        $zip->addFromString('xl/sharedStrings.xml',       $ssXml);
        $zip->addFromString('xl/worksheets/sheet1.xml',   $summarySheetXml);
        $zip->addFromString('xl/worksheets/sheet2.xml',   $procSheetXml);
        $zip->close();

        return $tmpFile;
    }

    // ============================================================
    // PRIVATE – date helpers
    // ============================================================
    private function formatDate($date): string
    {
        if (!$date) return '';
        return date('m/d/Y', strtotime($date));
    }

    private function formatDateLong($date): string
    {
        if (!$date) return '';
        return date('F d, Y', strtotime($date));
    }

    // ============================================================
    // PRIVATE – fetch & assemble procurement data
    // ============================================================
    private function getProcurementData($month = null, $year = null, $dateFrom = null, $dateTo = null)
    {
        $query = DB::table('purchase_requests as pr')
            ->leftJoin('emp_divisions as ed',                  'pr.division',         '=', 'ed.id')
            ->leftJoin('request_quotations as rq',             'pr.id',               '=', 'rq.pr_id')
            ->leftJoin('abstract_quotations as aq',            'pr.id',               '=', 'aq.pr_id')
            ->leftJoin('mooe_classifications as mc',           'aq.mode_procurement', '=', 'mc.id')
            ->leftJoin('inspection_acceptance_reports as iar', 'pr.id',               '=', 'iar.pr_id')
            ->select(
                'pr.id', 'pr.pr_no', 'pr.office', 'pr.division', 'ed.division_name',
                'pr.purpose', 'pr.remarks', 'pr.status', 'pr.funding_source', 'pr.date_pr',
                'aq.mode_procurement', 'mc.classification_name as mode_procurement_name',
                'rq.date_canvass', 'aq.date_abstract', 'aq.date_abstract_approved',
                'iar.date_iar', 'iar.date_inspected', 'iar.date_received'
            )
            ->whereNotNull('pr.date_pr')
            ->groupBy(
                'pr.id', 'pr.pr_no', 'pr.office', 'pr.division', 'ed.division_name',
                'pr.purpose', 'pr.remarks', 'pr.status', 'pr.funding_source', 'pr.date_pr',
                'aq.mode_procurement', 'mc.classification_name',
                'rq.date_canvass', 'aq.date_abstract', 'aq.date_abstract_approved',
                'iar.date_iar', 'iar.date_inspected', 'iar.date_received'
            );

        if ($dateFrom && $dateTo) {
            $query->whereBetween('pr.date_pr', [$dateFrom, $dateTo]);
        } elseif ($year) {
            $query->whereYear('pr.date_pr', '=', $year);
            if ($month) $query->whereMonth('pr.date_pr', '=', $month);
        }

        $query->orderBy('pr.pr_no', 'asc');
        $baseResults = $query->get();
        $results     = collect();

        foreach ($baseResults as $item) {
            $prItems = DB::table('purchase_request_items')
                ->where('pr_id', $item->id)
                ->select(DB::raw('SUM(est_total_cost) as total_abc'))
                ->first();

            $aqItems = DB::table('abstract_quotation_items as aqi')
                ->leftJoin('abstract_quotations as aq', 'aqi.abstract_id', '=', 'aq.id')
                ->leftJoin('suppliers as s',            'aqi.supplier',    '=', 's.id')
                ->where('aq.pr_id', $item->id)
                ->select(
                    DB::raw('SUM(aqi.total_cost) as contract_total'),
                    DB::raw('GROUP_CONCAT(DISTINCT s.company_name SEPARATOR ", ") as supplier_names')
                )
                ->first();

            $result = (object)[
                'id'                     => $item->id,
                'pr_no'                  => $item->pr_no,
                'office'                 => $item->office,
                'division'               => $item->division,
                'division_name'          => $item->division_name          ?? '',
                'purpose'                => $item->purpose,
                'remarks'                => $item->remarks,
                'status'                 => $item->status,
                'funding_source'         => $item->funding_source,
                'mode_procurement'       => $item->mode_procurement,
                'mode_procurement_name'  => $item->mode_procurement_name  ?? '',
                'date_canvass'           => $item->date_canvass,
                'date_abstract'          => $item->date_abstract,
                'date_abstract_approved' => $item->date_abstract_approved,
                'date_iar'               => $item->date_iar,
                'date_inspected'         => $item->date_inspected,
                'date_received'          => $item->date_received,
                'total_abc'              => $prItems->total_abc           ?? 0,
                'total_mooe'             => $prItems->total_abc           ?? 0,
                'total_co'               => 0,
                'contract_total'         => $aqItems->contract_total      ?? 0,
                'contract_mooe'          => $aqItems->contract_total      ?? 0,
                'contract_co'            => 0,
                'supplier'               => $aqItems->supplier_names      ?? '',
            ];

            if (empty($result->status))  $result->status  = $result->date_iar ? 'Completed' : 'Ongoing';
            if (empty($result->remarks)) $result->remarks = $result->status;

            $results->push($result);
        }

        return $results;
    }

    // ============================================================
    // DEBUG
    // ============================================================
    public function debug(Request $request)
    {
        $year  = $request->input('year', date('Y'));
        $month = $request->input('month');
        return response()->json([
            'year'                       => $year,
            'month'                      => $month,
            'purchase_requests_count'    => DB::table('purchase_requests')->whereYear('date_pr', '=', $year)->count(),
            'suppliers_count'            => DB::table('suppliers')->count(),
            'emp_divisions_count'        => DB::table('emp_divisions')->count(),
            'mooe_classifications_count' => DB::table('mooe_classifications')->count(),
            'sample_divisions'           => DB::table('emp_divisions')->select('id', 'division_name')->limit(10)->get(),
            'sample_classifications'     => DB::table('mooe_classifications')->select('id', 'classification_name')->limit(10)->get(),
        ]);
    }
}
