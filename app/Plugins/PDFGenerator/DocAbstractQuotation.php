<?php

namespace App\Plugins\PDFGenerator;

class DocAbstractQuotation extends PDF {

    // Store params for header repeat
    private $_printingAbstract = false;
    private $_totalWidth1 = 0;
    private $_bidderTotalWidth = 0;
    private $_bidderWidth = 0;
    private $_bidderCount = 0;
    private $_fontScale = 0;
    private $_bidderLists = [];

    public function header() {
        // Always call parent header first (logo, doc code, etc.)
        parent::header();

        // Repeat table header on page 2, 3, 4... during table rendering
        if ($this->_printingAbstract && $this->getPage() > 1) {

        $this->Ln(5); // ADD THIS — space between logo header and table header

        $this->printTableHeader(
                $this->_totalWidth1,
                $this->_bidderTotalWidth,
                $this->_bidderWidth,
                $this->_bidderCount,
                $this->_fontScale
            );

            // Push the top margin down so table data starts BELOW the repeated header
            $this->SetTopMargin($this->GetY());
        }
    }

    private function printTableHeader($totalWidth1, $bidderTotalWidth, $bidderWidth, $bidderCount, $fontScale) {
        $this->SetFont('helvetica', '', 8 + ($fontScale * 8));
        $this->Cell($totalWidth1 * 0.04, 4, '', 'LR', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 4, '', 'R', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 4, '', 'R', '', 'C');
        $this->Cell($totalWidth1 * 0.13, 4, '', 'R', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 4, '', 'R', '', 'C');
        $this->SetFont('helvetica', 'BI', 8 + ($fontScale * 8));
        $this->Cell($bidderTotalWidth, 3.6, "BIDDER'S QUOTATION AND OFFER", 'RB', '', 'C');
        $this->SetFont('helvetica', 'BI', 9 + ($fontScale * 9));
        $this->MultiCell(0, 3.5, "RECOMMEND the following", "R", "C", "");

        $this->SetFont('helvetica', '', 8 + ($fontScale * 8));
        $this->Cell($totalWidth1 * 0.04, 3.6, 'ITEM', 'LR', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 3.6, 'QTY', 'R', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 3.6, 'UNIT', 'R', '', 'C');
        $this->Cell($totalWidth1 * 0.13, 3.6, 'P A R T I C U L A R S', 'R', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 3.6, 'ABC', 'R', '', 'C');

        for ($bidCount = 1; $bidCount <= $bidderCount; $bidCount++) {
            $this->Cell($bidderWidth, 3.6, '', 'R', '', 'C');
        }

        $this->SetFont('helvetica', 'BI', 9 + ($fontScale * 9));
        $this->MultiCell(0, 3.5, "items to be AWARDED as ", "R", "C", "");

        $this->SetFont('helvetica', '', 6 + ($fontScale * 7));
        $this->Cell($totalWidth1 * 0.04, 3.6, 'NO.', 'LR', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 3.6, '', 'R', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 3.6, '', 'R', '', 'C');
        $this->Cell($totalWidth1 * 0.13, 3.6, '', 'R', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 3.6, '(Unit', 'R', '', 'C');

        foreach ($this->_bidderLists as $list) {
            $strLength = strlen($list['company_name']);

            if ($bidderCount == 1) {
                if ($strLength > 70) {
                    $this->Cell($bidderWidth, 3.6, substr(strtoupper($list['company_name']), 0, 70) . '...', 'RB', '', 'C');
                } else {
                    $this->Cell($bidderWidth, 3.6, strtoupper($list['company_name']), 'RB', '', 'C');
                }
            } else if ($bidderCount == 2) {
                if ($strLength > 60) {
                    $this->Cell($bidderWidth, 3.6, substr(strtoupper($list['company_name']), 0, 60) . '...', 'RB', '', 'C');
                } else {
                    $this->Cell($bidderWidth, 3.6, strtoupper($list['company_name']), 'RB', '', 'C');
                }
            } else if ($bidderCount == 3) {
                if ($strLength > 50) {
                    $this->Cell($bidderWidth, 3.6, substr(strtoupper($list['company_name']), 0, 30) . '...', 'RB', '', 'C');
                } else {
                    $this->Cell($bidderWidth, 3.6, strtoupper($list['company_name']), 'RB', '', 'C');
                }
            } else if ($bidderCount == 4) {
                if ($strLength > 30) {
                    $this->Cell($bidderWidth, 3.6, substr(strtoupper($list['company_name']), 0, 20) . '...', 'RB', '', 'C');
                } else {
                    $this->Cell($bidderWidth, 3.6, strtoupper($list['company_name']), 'RB', '', 'C');
                }
            } else if ($bidderCount >= 5) {
                if ($strLength > 15) {
                    $this->Cell($bidderWidth, 3.6, substr(strtoupper($list['company_name']), 0, 15) . '...', 'RB', '', 'C');
                } else {
                    $this->Cell($bidderWidth, 3.6, strtoupper($list['company_name']), 'RB', '', 'C');
                }
            }
        }

        $this->SetFont('helvetica', 'BI', 9 + ($fontScale * 9));
        $this->MultiCell(0, 3.5, "follows:", "RB", "C", "");

        $this->SetFont('helvetica', '', 8 + ($fontScale * 8));
        $this->Cell($totalWidth1 * 0.04, 3.6, '', 'LRB', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 3.6, '', 'RB', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 3.6, '', 'RB', '', 'C');
        $this->Cell($totalWidth1 * 0.13, 3.6, '', 'RB', '', 'C');
        $this->Cell($totalWidth1 * 0.04, 3.6, 'Cost)', 'RB', '', 'C');

        for ($bidCount = 1; $bidCount <= $bidderCount; $bidCount++) {
            if ($bidderCount == 3) {
                $this->SetFont('helvetica', '', 8 + ($fontScale * 8));
            } else if ($bidderCount == 4) {
                $this->SetFont('helvetica', '', 7 + ($fontScale * 7));
            } else if ($bidderCount >= 5) {
                $this->SetFont('helvetica', '', 5.5 + ($fontScale * 5.5));
            }

            $this->Cell($bidderWidth * 0.25, 3.6, 'Unit Cost', 'RB', '', 'C');
            $this->Cell($bidderWidth * 0.25, 3.6, 'Total Cost', 'RB', '', 'C');

            if ($bidderCount == 3) {
                $this->SetFont('helvetica', 'BI', 8 + ($fontScale * 8));
            } else if ($bidderCount == 4) {
                $this->SetFont('helvetica', 'BI', 7 + ($fontScale * 7));
            } else if ($bidderCount >= 5) {
                $this->SetFont('helvetica', 'BI', 5.5 + ($fontScale * 5.5));
            }

            $this->Cell($bidderWidth * 0.5, 3.6, 'Specification', 'RB', '', 'C');
        }

        $this->Cell(0, 3.6, '', 'RB', '', 'C');
        $this->Ln();
    }

    public function printAbstractQuotation($data) {
        $pageHeight = $this->h;
        $pageWidth = $this->w;
        $fontScale = $this->fontScale;

        $this->docId = $data->abstract->id;

        $prNo = $data->pr->pr_no;
        $purpose = $data->pr->purpose ?? '';

        $abstractDate = $data->abstract->date_abstract;

        $chairperson = strtoupper($data->sig_chairperson->name);
        $viceChairperson = strtoupper($data->sig_vice_chairperson->name);
        $member1 = strtoupper($data->sig_first_member->name);
        $member2 = strtoupper($data->sig_second_member->name);
        $member3 = strtoupper($data->sig_third_member->name);
        $endUser = strtoupper($data->sig_end_user->name);
        $modeProcurement = $data->abstract->mode_name ? $data->abstract->mode_name : '________________';

        $abstractSigs = [$chairperson, $viceChairperson, $member1,
                         $member2, $member3, $endUser];
        $sigPosition = ["Chairperson", "Vice Chairperson", "Member",
                        "Member", "Member", "End-user"];

        /* ------------------------------------- Start of Config ------------------------------------- */

        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->SetMargins(10, 35, 10);
        $this->SetHeaderMargin(10);
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $this->setLanguageArray($l);
        }

        $this->setFontSubsetting(true);

        /* ------------------------------------- End of Config ------------------------------------- */

        foreach ($data->abstract_items as $abstract) {
            $bidderCount = $abstract->bidder_count;
            $totalWidthDisplay = $pageWidth - 20;
            $totalWidth1 = $totalWidthDisplay * 0.83;
            $totalWidth2 = $totalWidthDisplay * 0.17;
            $bidderTotalWidth = $totalWidth1 * 0.71;

            if ($bidderCount != 0) {
                $bidderWidth = $bidderTotalWidth / $bidderCount;
            } else {
                $bidderWidth = $bidderTotalWidth / 3;
            }

            if ($bidderCount > 0) {
                $this->AddPage();

        /* ------------------------------------- Start of Doc ------------------------------------- */

                $this->SetFont('helvetica', 'B', 10 + ($fontScale * 10));
                $this->Cell($pageWidth * 0.948, 5, 'ABSTRACT OF BIDS AND QUOTATION', "", "", 'C');
                $this->Ln(10);

                $x = $this->GetX();
                $y = $this->GetY();

                $this->SetFont('helvetica', 'BI', 9 + ($fontScale * 9));
                $this->MultiCell($totalWidth1 / 2, 5.25, "Purchase Request No.: $prNo \nPMO/End-User : $endUser", "LTB", "L", "");

                $this->SetXY($x + ($totalWidth1 / 2), $y);
                $this->MultiCell($totalWidth1 / 2, 5.25, "Date Prepared: $abstractDate " .
                                                    "\n" .
                                                    "Mode of Procurement : $modeProcurement ", "RTB", "R", "");
                $this->SetXY($x + $totalWidth1, $y);

                $this->SetFont('helvetica', 'BI', 8 + ($fontScale * 8));
                $this->setCellHeightRatio(0.95);
                $this->MultiCell(0, 3.5, "based on the canvasses submitted,\n WE, the members of the " .
                                                    "Bids and\n Awards Committee (BAC) ", "TR", "C", "");

                $yProject = $this->GetY();
                $this->SetXY($x, $yProject);

                $this->SetFont('helvetica', 'BI', 9 + ($fontScale * 9));
                $this->MultiCell(
                    $totalWidth1,
                    6,
                    "Project Title/Purpose: $purpose",
                    "LRB",
                    "L",
                    false
                );

                $yAfterProject = $this->GetY();
                $this->SetXY($x + $totalWidth1, $yProject);
                $this->Cell($totalWidth2, $yAfterProject - $yProject, '', 'RB', 0, 'C');
                $this->SetY($yAfterProject);

                // Store bidder lists and params for header repeat on new pages
                $this->_bidderLists = [];
                foreach ($abstract->suppliers as $list) {
                    $this->_bidderLists[] = ['company_name' => $list->company_name];
                }
                $this->_totalWidth1      = $totalWidth1;
                $this->_bidderTotalWidth = $bidderTotalWidth;
                $this->_bidderWidth      = $bidderWidth;
                $this->_bidderCount      = $bidderCount;
                $this->_fontScale        = $fontScale;

                // Enable header repeat and print header on page 1
                $this->_printingAbstract = true;
                $this->printTableHeader($totalWidth1, $bidderTotalWidth, $bidderWidth, $bidderCount, $fontScale);

                // Render table — header() will auto-stamp on new pages
                $this->SetFont('helvetica', '', 8 + ($fontScale * 8));
                $this->htmlTable($abstract->table_data);

                // Disable header repeat and reset top margin back to normal
                $this->_printingAbstract = false;
                $this->SetTopMargin(35);

                $this->Ln(2.5);
                $this->SetFont('helvetica', '', 8 + ($fontScale * 8));
                $this->Cell(0, 0, "We hereby certify that we have witnessed the opening of bids/quotations and that the prices/quotations contained herein are the true and correct.");
                $this->Ln(5);

                $this->SetFont('helvetica', 'BI', 9 + ($fontScale * 9));
                $this->Cell($totalWidth1 + $totalWidth2, 5, "Recommendation:", '', 0, 'L', 0);
                $this->Ln(5);

                $this->Cell(0, 2, "", 'B', 1, 'L', 0);
                $this->Cell(0, 2, "", 'B', 1, 'L', 0);
                $this->Cell(0, 2, "", 'B', 1, 'L', 0);
                $this->Cell(0, 2, "", 'B', 1, 'L', 0);
                $this->Ln(5);

                $this->SetFont('helvetica', 'B', 10 + ($fontScale * 10));
                $this->SetTextColor(0, 0, 0);
                $this->Ln(5);

                $this->Cell($totalWidthDisplay * 0.83, 5, "BIDS AND AWARDS COMITTEE:", '', 0, 'L', 0);
                $this->SetFont('helvetica', '', 10 + ($fontScale * 10));
                $this->Cell($totalWidthDisplay * 0.32, 5, "", '', 0, 'L', 0);
                $this->Ln();
                $this->SetFont('helvetica', 'B', 9 + ($fontScale * 9));
                $this->Cell(0, 8, " ", '', 0, 'L', 0);
                $this->Ln();

                $signatoryIDs = [];

                foreach ($abstractSigs as $absSigCtr => $absSig) {
                    if (!empty($absSig)) {
                        $signatoryIDs[] = $absSigCtr;
                    }
                }

                $signatoryCount = count($signatoryIDs);
                $columWidth = ($totalWidthDisplay - 15) / $signatoryCount;
                $columWidthSpace = 15 / 12;

                foreach ($abstractSigs as $absSigCtr => $absSig) {
                    if (!empty($absSig)) {
                        $this->Cell($columWidthSpace, 5);
                        $this->Cell($columWidth, 5, $absSig, 'B', 0, 'C');
                        $this->Cell($columWidthSpace, 5);
                    }
                }

                $this->SetFont('helvetica', '', 9 + ($fontScale * 9));
                $this->Ln();

                foreach ($sigPosition as $titleCtr => $title) {
                    if (in_array($titleCtr, $signatoryIDs)) {
                        $this->Cell($columWidthSpace, 5);
                        $this->Cell($columWidth, 5, $title, 0, 0, 'C');
                        $this->Cell($columWidthSpace, 5);
                    }
                }
            }

        /* ------------------------------------- End of Doc ------------------------------------- */
        }
    }
}
