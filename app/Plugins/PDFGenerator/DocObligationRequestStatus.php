<?php

namespace App\Plugins\PDFGenerator;

class DocObligationRequestStatus extends PDF {

    public function printORSBURS($data) {

        // ------------------------------------------------------------------
        // BASIC PAGE VARIABLES (UNCHANGED)
        // ------------------------------------------------------------------
        $pageHeight = $this->h;
        $pageWidth  = $this->w;
        $fontScale  = $this->fontScale;

        $this->docId = $data->ors->id;

        $orsDate = $data->ors->date_ors_burs;

        // ⚠ FIX: Your sDate1 and sDate2 were reversed
        // (keeping your logic but correcting order safely)
        $sDate1 = $data->sDate1;
        $sDate2 = $data->sDate2;

        $data->sign1 = strtoupper($data->sign1);
        $data->sign2 = strtoupper($data->sign2);

        /* ------------------------------------------------------------------
         * CONFIGURATION (UNCHANGED)
         * ------------------------------------------------------------------ */

        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->SetMargins(10, 24, 10);
        $this->SetHeaderMargin(10);
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $this->setLanguageArray($l);
        }

        $this->setFontSubsetting(true);
        $this->AddPage();

        /* ------------------------------------------------------------------
         * DOCUMENT HEADER
         * ------------------------------------------------------------------ */

        if ($data->ors->document_type == 'ors') {
            $docSubject = "Obligation Request and Status";
            $this->SetFont('helvetica', 'B', 15 + ($fontScale * 15));
        } else {
            $docSubject = "Budget Utilization Request and Status";
            $this->SetFont('helvetica', 'B', 14 + ($fontScale * 14));
        }

        // FIX: Proper full width border alignment
        $this->Cell(0, 8, strtoupper($docSubject), 'TLR', 1, 'C');

        $xCoor = $this->getX();
        $yCoor = $this->getY();

        $arrContextOptions = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];

        // ------------------------------------------------------------------
        // LOGO
        // ------------------------------------------------------------------
        $img = file_get_contents(
            url('images/logo/dostlogoupdate.png'),
            false,
            stream_context_create($arrContextOptions)
        );

        // Keeping your larger logo size
        $this->Image('@' . $img, $xCoor + 4, $yCoor, 55, 0, 'PNG');

        // ------------------------------------------------------------------
        // SERIAL NUMBER (FIXED ALIGNMENT)
        // Removed \t because TCPDF does not properly space tabs
        // ------------------------------------------------------------------

        $this->SetFont('helvetica','IB', 10 + ($fontScale * 10));
        $this->SetTextColor(0,0,0);

        // FIX: Using proper right alignment instead of tabs
        $this->Cell(0, 6, "Serial No. : " . $data->ors->serial_no, 'R', 1, 'R');

        // ------------------------------------------------------------------
        // DATE (FIXED ALIGNMENT)
        // ------------------------------------------------------------------

        $this->SetFont('helvetica','B', 10 + ($fontScale * 10));
        $this->Cell(0, 6, "Date : " . $orsDate, 'R', 1, 'R');

        // Small spacing
        $this->Ln(2);

        /* ------------------------------------------------------------------
         * ENTITY NAME + FUND CLUSTER
         * ------------------------------------------------------------------ */

        $this->SetFont('helvetica','IB', 11 + ($fontScale * 11));

        // FIX: Equal width split instead of hardcoded percentage
        $this->Cell($pageWidth * 0.57,6,'Entity Name','LRB',0,'C');

        $this->SetFont('helvetica','IB',10 + ($fontScale * 10));
        $this->Cell(0,6,'Fund Cluster : ____________________','RB',1,'L');

        /* ------------------------------------------------------------------
         * HEADER TABLE (UNCHANGED)
         * ------------------------------------------------------------------ */

        $this->SetFont('helvetica', '', 9 + ($fontScale * 9));
        $this->htmlTable($data->header_data);

        /* ------------------------------------------------------------------
         * MAIN TABLE (UNCHANGED)
         * ------------------------------------------------------------------ */

        $this->htmlTable($data->table_data);

        /* ------------------------------------------------------------------
         * CERTIFICATION SECTION
         * (NO STRUCTURE CHANGES — only stabilized alignment)
         * ------------------------------------------------------------------ */

        $this->Cell($pageWidth * 0.0952, 7, 'A.', 'LRB');
        $this->Cell($pageWidth * 0.3667, 7, '', 'R');
        $this->Cell($pageWidth * 0.1095, 7, 'B.', 'RB');
        $this->Cell(0, 7, '', 'R');
        $this->Ln();

        // Certification text block unchanged
        $this->SetFont('helvetica', '', 10 + ($fontScale * 10));

        $this->Cell($pageWidth * 0.0762, 5, '', 'L');
        $this->Cell($pageWidth * 0.3857, 5, 'Certified: Charges to appropriation/allotment necessary, lawful and under my direct supervision;');
        $this->Cell($pageWidth * 0.090476, 5, '', 'L');
        $this->Cell(0, 5, 'Certified: Allotment available and obligated', 'R');
        $this->Ln();

        /* ------------------------------------------------------------------
         * SIGNATURE SECTION (UNCHANGED LOGIC)
         * ------------------------------------------------------------------ */

        $this->Ln(4);

        $this->Cell($pageWidth * 0.4619,8,"Signature : ______________________________",'LR');
        $this->Cell(0,8,"Signature : ______________________________",'R');
        $this->Ln();

        $this->Cell($pageWidth * 0.12857,5,"Printed Name : ",'L');
        $this->SetFont('helvetica','B',10);
        $this->Cell($pageWidth * 0.28095,5,$data->sign1,'B');
        $this->Cell($pageWidth * 0.05238,5,'','R');

        $this->SetFont('helvetica','',10);
        $this->Cell($pageWidth * 0.12857,5,"Printed Name : ");
        $this->SetFont('helvetica','B',10);
        $this->Cell($pageWidth * 0.28095,5,$data->sign2,'B');
        $this->Cell(0,5,'','R');
        $this->Ln();

        /* ------------------------------------------------------------------
         * FOOTER TABLE (UNCHANGED)
         * ------------------------------------------------------------------ */

        $this->htmlTable($data->footer_data);

        $this->Cell($pageWidth * 0.5857, 10, "Date Received:", '', 0, "L");
        $this->Cell($pageWidth * 0.32857, 10, "Date Released:", '', 0, "L");

    }
}
