<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SummaryLDDAP as Summary;
use App\Models\SummaryLDDAPItem as SummaryItem;
use App\Models\ListDemandPayable;
use App\Models\ListDemandPayableItem;

use App\User;
use App\Models\EmpGroup;
use App\Models\EmpDivision;
use App\Models\Signatory;
use App\Models\DocumentLog as DocLog;
use App\Models\PaperSize;
use App\Models\Supplier;
use App\Models\MdsGsb;
use DB;
use Auth;
use Carbon\Carbon;

class SummaryLDDAP extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $keyword = trim($request->keyword);

        // Get module access
        $module = 'pay_summary';
        $isAllowedCreate = Auth::user()->getModuleAccess($module, 'create');
        $isAllowedUpdate = Auth::user()->getModuleAccess($module, 'update');
        $isAllowedDelete = Auth::user()->getModuleAccess($module, 'delete');
        $isAllowedDestroy = Auth::user()->getModuleAccess($module, 'destroy');
        $isAllowedApproval = Auth::user()->getModuleAccess($module, 'approval');
        $isAllowedApprove = Auth::user()->getModuleAccess($module, 'approve');
        $isAllowedSubmission = Auth::user()->getModuleAccess($module, 'submission');

        // Main data
        $paperSizes = PaperSize::orderBy('paper_type')->get();
        $summaryData = Summary::whereNull('deleted_at');

        if (!empty($keyword)) {
            $summaryData = $summaryData->where(function($qry) use ($keyword) {
                $qry->where('id', 'like', "%$keyword%")
                    ->orWhere('department', 'like', "%$keyword%")
                    ->orWhere('entity_name', 'like', "%$keyword%")
                    ->orWhere('operating_unit', 'like', "%$keyword%")
                    ->orWhere('fund_cluster', 'like', "%$keyword%")
                    ->orWhere('sliiae_no', 'like', "%$keyword%")
                    ->orWhere('date_sliiae', 'like', "%$keyword%")
                    ->orWhere('to', 'like', "%$keyword%")
                    ->orWhere('bank_name', 'like', "%$keyword%")
                    ->orWhere('bank_address', 'like', "%$keyword%")
                    ->orWhere('lddap_no_pcs', 'like', "%$keyword%")
                    ->orWhere('total_amount_words', 'like', "%$keyword%")
                    ->orWhere('total_amount', 'like', "%$keyword%")
                    ->orWhere('status', 'like', "%$keyword%");
            });
        }

        $summaryData = $summaryData->sortable(['created_at' => 'desc'])->paginate(15);

        return view('modules.payment.summary.index', [
            'list' => $summaryData,
            'keyword' => $keyword,
            'paperSizes' => $paperSizes,
            'isAllowedCreate' => $isAllowedCreate,
            'isAllowedUpdate' => $isAllowedUpdate,
            'isAllowedDelete' => $isAllowedDelete,
            'isAllowedDestroy' => $isAllowedDestroy,
            'isAllowedApproval' => $isAllowedApproval,
            'isAllowedApprove' => $isAllowedApprove,
            'isAllowedSubmission'=> $isAllowedSubmission,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCreate() {
        $mdsGSBs = MdsGsb::all();
        $signatories = Signatory::addSelect([
            'name' => User::select(DB::raw('CONCAT(firstname, " ", lastname) AS name'))
                          ->whereColumn('id', 'signatories.emp_id')
                          ->limit(1)
        ])->where('is_active', 'y')->get();

        foreach ($signatories as $sig) {
            $sig->module = json_decode($sig->module);
        }

        return view('modules.payment.summary.create', compact(
            'mdsGSBs', 'signatories'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $mdsGsbID = $request->mds_gsb_id;
        $department = $request->department;
        $entityName = $request->entity_name;
        $operatingUnit = $request->operating_unit;
        $fundCluster = $request->fund_cluster;
        $sliiaeNo = $request->sliiae_no;
        $sliiaeDate = $request->sliiae_date;
        $to = $request->to;
        $bankName = $request->bank_name;
        $bankAddress = $request->bank_address;
        $totalAmount = $request->total_amount;
        $totalAmountWords = $request->total_amount_words;
        $sigCertCorrect = $request->cert_correct;
        $sigApprovedBy = $request->approved_by;
        $sigDeliveredBy = $request->delivered_by;

        $lddapIDs = $request->lddap_id;
        $dateIssues = $request->date_issue;
        $totals = $request->total;
        $allotmentPSs = $request->allotment_ps;
        $allotmentMOOEs = $request->allotment_mooe;
        $allotmentCOs = $request->allotment_co;
        $allotmentFEs = $request->allotment_fe;
        $allotmentPSRemarks = $request->allotment_ps_remarks;
        $allotmentMOOERemarks = $request->allotment_mooe_remarks;
        $allotmentCORemarks = $request->allotment_co_remarks;
        $allotmentFERemarks = $request->allotment_fe_remarks;

        $countLDDAP = count($lddapIDs);
        $documentType = 'Summary of LDDAP-ADAs Issued and Invalidated ADA Entries';
        $routeName = 'summary';
        //dd($mdsGsbID);


            $instanceSummary = new Summary;
            $instanceSummary->mds_gsb_id = $mdsGsbID;
            $instanceSummary->department = $department;
            $instanceSummary->entity_name = $entityName;
            $instanceSummary->operating_unit = $operatingUnit;
            $instanceSummary->fund_cluster = $fundCluster;
            $instanceSummary->sliiae_no = $sliiaeNo;
            $instanceSummary->date_sliiae = $sliiaeDate;
            $instanceSummary->to = $to;
            $instanceSummary->bank_name = $bankName;
            $instanceSummary->bank_address = $bankAddress;
            $instanceSummary->total_amount = $totalAmount;
            $instanceSummary->total_amount_words = $totalAmountWords;
            $instanceSummary->lddap_no_pcs = $countLDDAP;
            $instanceSummary->sig_cert_correct = $sigCertCorrect;
            $instanceSummary->sig_approved_by = $sigApprovedBy;
            $instanceSummary->sig_delivered_by = $sigDeliveredBy;
            $instanceSummary->save();

            $lastSummary = Summary::orderBy('created_at', 'desc')->first();
            $lastID = $lastSummary->id;

            if (is_array($lddapIDs)) {
                if (count($lddapIDs) > 0) {
                    foreach ($lddapIDs as $ctr => $lddapID) {
                        $itemNo = $ctr + 1;
                        $instanceSummaryItem = new SummaryItem;
                        $instanceSummaryItem->sliiae_id = $lastID;
                        $instanceSummaryItem->item_no = $itemNo;
                        $instanceSummaryItem->lddap_id = $lddapID;
                        $instanceSummaryItem->date_issue = $dateIssues[$ctr];
                        $instanceSummaryItem->total = $totals[$ctr];
                        $instanceSummaryItem->allotment_ps = $allotmentPSs[$ctr];
                        $instanceSummaryItem->allotment_mooe = $allotmentMOOEs[$ctr];
                        $instanceSummaryItem->allotment_co = $allotmentCOs[$ctr];
                        $instanceSummaryItem->allotment_fe = $allotmentFEs[$ctr];
                        $instanceSummaryItem->allotment_ps_remarks = $allotmentPSRemarks[$ctr];
                        $instanceSummaryItem->allotment_mooe_remarks = $allotmentMOOERemarks[$ctr];
                        $instanceSummaryItem->allotment_co_remarks = $allotmentCORemarks[$ctr];
                        $instanceSummaryItem->allotment_fe_remarks = $allotmentFERemarks[$ctr];
                        $instanceSummaryItem->save();
                    }
                }
            }

            /*
            $msg = "$documentType successfully created.";
            Auth::user()->log($request, $msg);
            return redirect()->route($routeName)
                             ->with('success', $msg);*/try {
        } catch (\Throwable $th) {
            /*
            $msg = "Unknown error has occured. Please try again.";
            Auth::user()->log($request, $msg);
            return redirect(url()->previous())
                                 ->with('failed', $msg);*/
        }
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

    public function getListLDDAP(Request $request) {
        $search = trim($request->search);
        $lddapData = ListDemandPayable::select('id', 'lddap_ada_no',
                                               'total_amount', 'date_lddap');

        if ($search) {
            $lddapData = $lddapData->where('lddap_ada_no', 'like', "%$search%");
        }

        $lddapData = $lddapData->orderBy('lddap_ada_no')->get();

        return response()->json($lddapData);
    }
}
