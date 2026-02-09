<?php

namespace App\Http\Controllers;

use App\PreventiveModel;
use App\Properties;
use Illuminate\Http\Request;
use App\Models\InventoryStockItem;
use App\Models\InventoryStock;
use App\Models\ItemUnitIssue;
use App\Models\InventoryClassification;
use App\Models\InventoryStockIssue;
use App\Models\Signatory;
use App\Models\InventoryStockIssueItem;
use App\Models\EmpAccount;
use App\Models\PurchaseRequest;
use App\Models\PurchaseJobOrderItem;
use App\Models\FundingProject;
use App\Models\IndustrySector;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ParController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Increase execution time
        set_time_limit(500);

        $emp_accounts = DB::table('emp_accounts')->get();

        // Get current page
        $page = $request->get('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Count total records with WHERE condition
        $total = DB::table('inventory_stock_items')
            ->leftJoin('item_classifications', 'inventory_stock_items.item_classification', '=', 'item_classifications.id')
            ->leftJoin('item_unit_issues', 'inventory_stock_items.unit_issue', '=', 'item_unit_issues.id')
            ->leftJoin('inventory_stocks', 'inventory_stock_items.inv_stock_id', '=', 'inventory_stocks.id')
            ->leftJoin('purchase_requests', 'purchase_requests.id', '=', 'inventory_stock_items.pr_id')
            ->leftJoin('funding_projects', 'funding_projects.id', '=', 'purchase_requests.funding_source')
            ->leftJoin('industry_sectors', 'funding_projects.industry_sector', '=', 'industry_sectors.id')
            ->leftJoin('inventory_stock_issues', 'inventory_stocks.id', '=', 'inventory_stock_issues.inv_stock_id')
            ->leftJoin('purchase_job_order_items', 'inventory_stock_items.po_item_id', '=', 'purchase_job_order_items.id')
            ->leftJoin('emp_accounts', 'inventory_stock_issues.sig_received_by', '=', 'emp_accounts.id')
            ->leftJoin('inventory_stock_classifications', 'inventory_stocks.inventory_classification', '=', 'inventory_stock_classifications.id')
            ->where('inventory_stock_classifications.classification_name', 'Property Aknowledgement Receipt (PAR)')
            ->count();

        // Get paginated data using raw query
        $items = DB::select("
            SELECT
                inventory_stock_items.id,
                description,
                pr_no,
                inventory_no,
                inventory_stock_items.quantity,
                unit_cost,
                total_cost,
                sector_name,
                date_po,
                inventory_stock_classifications.classification_name,
                firstname,
                lastname,
                inventory_stock_items.care_of_to,
                inventory_stock_items.date_of_issuance,
                inventory_stock_items.status
            FROM inventory_stock_items
            LEFT JOIN item_classifications ON inventory_stock_items.item_classification = item_classifications.id
            LEFT JOIN item_unit_issues ON inventory_stock_items.unit_issue = item_unit_issues.id
            LEFT JOIN inventory_stocks ON inventory_stock_items.inv_stock_id = inventory_stocks.id
            LEFT JOIN purchase_requests ON purchase_requests.id = inventory_stock_items.pr_id
            LEFT JOIN funding_projects ON funding_projects.id = purchase_requests.funding_source
            LEFT JOIN industry_sectors ON funding_projects.industry_sector = industry_sectors.id
            LEFT JOIN inventory_stock_issues ON inventory_stocks.id = inventory_stock_issues.inv_stock_id
            LEFT JOIN purchase_job_order_items ON inventory_stock_items.po_item_id = purchase_job_order_items.id
            LEFT JOIN emp_accounts ON inventory_stock_issues.sig_received_by = emp_accounts.id
            LEFT JOIN inventory_stock_classifications ON inventory_stocks.inventory_classification = inventory_stock_classifications.id
            WHERE inventory_stock_classifications.classification_name = 'Property Aknowledgement Receipt (PAR)'
            ORDER BY inventory_stock_items.id DESC
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        // Create paginator manually
        $description = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('modules.inventory.Par.par', ['description' => $description, 'emp_accounts' => $emp_accounts]);
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
    public function edit(Request $Request)
    {
                        // print_r($Request->all());
    $id = $Request->input('id');
    $care_of_to = $Request->input('care_of_to');
    $date_of_issuance = $Request->input('date_of_issuance');
    $status = $Request->input('status');

    DB::table('inventory_stock_items')
    ->where('id', $id)
    ->update([
        'care_of_to'=> $care_of_to,
        'date_of_issuance'=> $date_of_issuance,
        'status'=> $status,
            ]);
    return back();
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
