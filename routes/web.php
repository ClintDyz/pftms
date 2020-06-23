<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

/*===================== ACCOUNT MANAGEMENT ROUTES =====================*/

// Profile Module
Route::get('profile/registration', 'AccountController@showCreateProfile')
     ->name('profile-registration');
Route::post('profile/register', 'AccountController@storeProfile')
     ->name('profile-store');

// Registration Routes...
Route::get('register', 'AccountController@showCreateProfile')
     ->name('register');
Route::post('register', 'AccountController@storeProfile');

Route::middleware(['web', 'auth'])->group(function () {

    /*===================== INVENTORY ROUTES =====================*/

    // Stocks
    Route::any('inventory/stocks', [
        'uses' => 'InventoryController@index',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory');
    Route::get('inventory/stocks/create/{poNo}', [
        'uses' => 'InventoryController@create',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-create');
    Route::get('inventory/stocks/show/{key}', [
        'uses' => 'InventoryController@show',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-show');
    Route::get('inventory/stocks/show-create/{classification}', [
        'uses' => 'InventoryController@showCreate',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-show-create');
    Route::get('inventory/stocks/edit/{inventoryNo}', [
        'uses' => 'InventoryController@edit',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-edit');
    Route::get('inventory/stocks/issued/{inventoryNo}', [
        'uses' => 'InventoryController@showIssuedTo',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-issued');
    Route::post('inventory/stocks/store/{inventoryNo}', [
        'uses' => 'InventoryController@store',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-store');
    Route::post('inventory/stocks/issue-stocks/{key}', [
        'uses' => 'InventoryController@issueStocks',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-issue-stocks');
    Route::post('inventory/stocks/update/{inventoryNo}', [
        'uses' => 'InventoryController@update',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-update');
    Route::post('inventory/stocks/update-stocks/{inventoryNo}', [
        'uses' => 'InventoryController@updateStocks',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-update-stocks');
    Route::post('inventory/stocks/update-serial-no/{inventoryNo}', [
        'uses' => 'InventoryController@updateSerialNo',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-update-serial-no');
    Route::post('inventory/stocks/delete/{inventoryNo}', [
        'uses' => 'InventoryController@delete',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-delete');
    Route::post('inventory/stocks/set-issued/{inventoryNo}', [
        'uses' => 'InventoryController@setIssued',
        'middleware' => 'roles',
        'roles' => ['Developer', 'Supply & Property Officer', 'PSTD']
    ])->name('inventory-set-issued');

    /*===================== REPORT ROUTES =====================*/

    // Under Development

    /*===================== VOUCHER TRACKING ROUTES =====================*/

    Route::get('voucher-tracking/{toggle}', 'VoucherLogController@index');
    Route::get('voucher-tracking/generate-table/{toggle}', 'VoucherLogController@show');














    /*===================== ACCOUNT MANAGEMENT ROUTES =====================*/

    // Profile Module
    Route::get('profile', 'AccountController@indexProfile')
         ->name('profile');
    Route::get('profile/edit', 'AccountController@showEditProfile')
         ->name('profile-show-edit');
    Route::post('profile/update', 'AccountController@updateProfile')
         ->name('profile-update');
    Route::post('profile/delete', 'AccountController@deleteProfile')
         ->name('profile-delete');
    Route::post('profile/destroy', 'AccountController@destroyProfile')
         ->name('profile-destroy');

    /*===================== OTHER ROUTES =====================*/

    // Search all
    Route::post('search', 'HomeController@indexSearchAll')->name('search-all');

    // Dashboard Module
    Route::get('/', 'HomeController@index')->name('dashboard');

    // Document Printing Module
    Route::any('print/{key}', 'PrintController@index')->name('doc-print');

    // Document Attachment Moduule
    Route::get('attachment/get/{parentID}', 'AttachmentController@showAttachment')->name('doc-attach');
    Route::post('attachment/store', 'AttachmentController@store')->name('doc-attach-store');
    Route::post('attachment/update/{id}', 'AttachmentController@update')->name('doc-attach-update');
    Route::post('attachment/destroy/{id}', 'AttachmentController@destroy')->name('doc-attach-destroy');

    // Notification Module
    Route::get('notification/mark-as-read/{notifID}', 'NotificationController@makeAsRead');
    Route::get('notification/display', 'NotificationController@displayNotifications');
    Route::get('notification/show-all', 'NotificationController@showAllNotifications');
});

Route::middleware(['web', 'auth', 'moduleaccess'])->group(function () {

    /*===================== CASH ADVANCE, REIMBURSEMENT, & LIQUIDATION ROUTES =====================*/

    Route::any('cadv-reim-liquidation/ors-burs', [
        'uses' => 'ObligationRequestStatusController@indexCA',
        'module' => 'ca_ors_burs',
        'access' => 'is_allowed'
    ])->name('ca-ors-burs');
    Route::get('cadv-reim-liquidation/ors-burs/show-create', [
        'uses' => 'ObligationRequestStatusController@showCreate',
        'module' => 'ca_ors_burs',
        'access' => 'create'
    ])->name('ca-ors-burs-show-create');
    Route::post('cadv-reim-liquidation/ors-burs/store', [
        'uses' => 'ObligationRequestStatusController@store',
        'module' => 'ca_ors_burs',
        'access' => 'create'
    ])->name('ca-ors-burs-store');
    Route::get('cadv-reim-liquidation/ors-burs/show-edit/{id}', [
        'uses' => 'ObligationRequestStatusController@showEdit',
        'module' => 'ca_ors_burs',
        'access' => 'update'
    ])->name('ca-ors-burs-show-edit');
    Route::post('cadv-reim-liquidation/ors-burs/update/{id}', [
        'uses' => 'ObligationRequestStatusController@update',
        'module' => 'ca_ors_burs',
        'access' => 'update'
    ])->name('ca-ors-burs-update');
    Route::post('cadv-reim-liquidation/ors-burs/delete/{id}', [
        'uses' => 'ObligationRequestStatusController@delete',
        'module' => 'ca_ors_burs',
        'access' => 'delete'
    ])->name('ca-ors-burs-delete');
    Route::post('cadv-reim-liquidation/ors-burs/destroy/{id}', [
        'uses' => 'ObligationRequestStatusController@destroy',
        'module' => 'ca_ors_burs',
        'access' => 'destroy'
    ])->name('ca-ors-burs-destroy');
    Route::get('cadv-reim-liquidation/ors-burs/show-issue/{id}', [
        'uses' => 'ObligationRequestStatusController@showIssue',
        'module' => 'ca_ors_burs',
        'access' => 'issue'
    ])->name('ca-ors-burs-show-issue');
    Route::post('cadv-reim-liquidation/ors-burs/issue/{id}', [
        'uses' => 'ObligationRequestStatusController@issue',
        'module' => 'ca_ors_burs',
        'access' => 'issue'
    ])->name('ca-ors-burs-issue');
    Route::get('cadv-reim-liquidation/ors-burs/show-receive/{id}', [
        'uses' => 'ObligationRequestStatusController@showReceive',
        'module' => 'ca_ors_burs',
        'access' => 'receive'
    ])->name('ca-ors-burs-show-receive');
    Route::post('cadv-reim-liquidation/ors-burs/receive/{id}', [
        'uses' => 'ObligationRequestStatusController@receive',
        'module' => 'ca_ors_burs',
        'access' => 'receive'
    ])->name('ca-ors-burs-receive');
    Route::get('cadv-reim-liquidation/ors-burs/show-issue-back/{id}', [
        'uses' => 'ObligationRequestStatusController@showIssueback',
        'module' => 'ca_ors_burs',
        'access' => 'issue_back'
    ])->name('ca-ors-burs-show-issue-back');
    Route::post('cadv-reim-liquidation/ors-burs/issue-back/{id}', [
        'uses' => 'ObligationRequestStatusController@issueBack',
        'module' => 'ca_ors_burs',
        'access' => 'issue_back'
    ])->name('ca-ors-burs-issue-back');
    Route::get('cadv-reim-liquidation/ors-burs/show-receive-back/{id}', [
        'uses' => 'ObligationRequestStatusController@showReceiveBack',
        'module' => 'ca_ors_burs',
        'access' => 'receive_back'
    ])->name('ca-ors-burs-show-receive-back');
    Route::post('cadv-reim-liquidation/ors-burs/receive-back/{id}', [
        'uses' => 'ObligationRequestStatusController@receiveBack',
        'module' => 'ca_ors_burs',
        'access' => 'receive_back'
    ])->name('ca-ors-burs-receive-back');
    Route::get('cadv-reim-liquidation/ors-burs/show-obligate/{id}', [
        'uses' => 'ObligationRequestStatusController@showObligate',
        'module' => 'ca_ors_burs',
        'access' => 'obligate'
    ])->name('ca-ors-burs-show-obligate');
    Route::post('cadv-reim-liquidation/ors-burs/obligate/{id}', [
        'uses' => 'ObligationRequestStatusController@obligate',
        'module' => 'ca_ors_burs',
        'access' => 'obligate'
    ])->name('ca-ors-burs-obligate');
    Route::get('cadv-reim-liquidation/ors-burs/show-remarks/{id}', [
        'uses' => 'ObligationRequestStatusController@showLogRemarks',
        'module' => 'ca_ors_burs',
        'access' => 'is_allowed'
    ])->name('ca-ors-burs-show-remarks');
    Route::post('cadv-reim-liquidation/ors-burs/create-remarks/{id}', [
        'uses' => 'ObligationRequestStatusController@logRemarks',
        'module' => 'ca_ors_burs',
        'access' => 'is_allowed'
    ])->name('ca-ors-burs-store-remarks');

    // Disbursement Voucher
    Route::any('cadv-reim-liquidation/dv', [
        'uses' => 'DisbursementVoucherController@indexCA',
        'module' => 'ca_dv',
        'access' => 'is_allowed'
    ])->name('ca-dv');
    Route::get('cadv-reim-liquidation/dv/show-create', [
        'uses' => 'DisbursementVoucherController@showCreate',
        'module' => 'ca_dv',
        'access' => 'create'
    ])->name('ca-dv-show-create');
    Route::get('cadv-reim-liquidation/dv/show-create-from-ors/{orsID}', [
        'uses' => 'DisbursementVoucherController@showCreateFromORS',
        'module' => 'ca_dv',
        'access' => 'create'
    ])->name('ca-dv-show-create-ors');
    Route::post('cadv-reim-liquidation/dv/store', [
        'uses' => 'DisbursementVoucherController@store',
        'module' => 'ca_dv',
        'access' => 'create'
    ])->name('ca-dv-store');
    Route::get('cadv-reim-liquidation/dv/show-edit/{id}', [
        'uses' => 'DisbursementVoucherController@showEdit',
        'module' => 'ca_dv',
        'access' => 'update'
    ])->name('ca-dv-show-edit');
    Route::post('cadv-reim-liquidation/dv/update/{id}', [
        'uses' => 'DisbursementVoucherController@update',
        'module' => 'ca_dv',
        'access' => 'update'
    ])->name('ca-dv-update');
    Route::post('cadv-reim-liquidation/dv/delete/{id}', [
        'uses' => 'DisbursementVoucherController@delete',
        'module' => 'ca_dv',
        'access' => 'delete'
    ])->name('ca-dv-delete');
    Route::post('cadv-reim-liquidation/dv/destroy/{id}', [
        'uses' => 'DisbursementVoucherController@destroy',
        'module' => 'ca_dv',
        'access' => 'destroy'
    ])->name('ca-dv-destroy');
    Route::get('cadv-reim-liquidation/dv/show-issue/{id}', [
        'uses' => 'DisbursementVoucherController@showIssue',
        'module' => 'ca_dv',
        'access' => 'issue'
    ])->name('ca-dv-show-issue');
    Route::post('cadv-reim-liquidation/dv/issue/{id}', [
        'uses' => 'DisbursementVoucherController@issue',
        'module' => 'ca_dv',
        'access' => 'issue'
    ])->name('ca-dv-issue');
    Route::get('cadv-reim-liquidation/dv/show-receive/{id}', [
        'uses' => 'DisbursementVoucherController@showReceive',
        'module' => 'ca_dv',
        'access' => 'receive'
    ])->name('ca-dv-show-receive');
    Route::post('cadv-reim-liquidation/dv/receive/{id}', [
        'uses' => 'DisbursementVoucherController@receive',
        'module' => 'ca_dv',
        'access' => 'receive'
    ])->name('ca-dv-receive');
    Route::get('cadv-reim-liquidation/dv/show-issue-back/{id}', [
        'uses' => 'DisbursementVoucherController@showIssueback',
        'module' => 'ca_dv',
        'access' => 'issue_back'
    ])->name('ca-dv-show-issue-back');
    Route::post('cadv-reim-liquidation/dv/issue-back/{id}', [
        'uses' => 'DisbursementVoucherController@issueBack',
        'module' => 'ca_dv',
        'access' => 'issue_back'
    ])->name('ca-dv-issue-back');
    Route::get('cadv-reim-liquidation/dv/show-receive-back/{id}', [
        'uses' => 'DisbursementVoucherController@showReceiveBack',
        'module' => 'ca_dv',
        'access' => 'receive_back'
    ])->name('ca-dv-show-receive-back');
    Route::post('cadv-reim-liquidation/dv/receive-back/{id}', [
        'uses' => 'DisbursementVoucherController@receiveBack',
        'module' => 'ca_dv',
        'access' => 'receive_back'
    ])->name('ca-dv-receive-back');
    Route::get('cadv-reim-liquidation/dv/show-payment/{id}', [
        'uses' => 'DisbursementVoucherController@showPayment',
        'module' => 'ca_dv',
        'access' => 'payment'
    ])->name('ca-dv-show-payment');
    Route::post('cadv-reim-liquidation/dv/payment/{id}', [
        'uses' => 'DisbursementVoucherController@payment',
        'module' => 'ca_dv',
        'access' => 'payment'
    ])->name('ca-dv-payment');
    Route::get('cadv-reim-liquidation/dv/show-remarks/{id}', [
        'uses' => 'DisbursementVoucherController@showLogRemarks',
        'module' => 'ca_dv',
        'access' => 'is_allowed'
    ])->name('ca-dv-show-remarks');
    Route::post('cadv-reim-liquidation/dv/create-remarks/{id}', [
        'uses' => 'DisbursementVoucherController@logRemarks',
        'module' => 'ca_dv',
        'access' => 'is_allowed'
    ])->name('ca-dv-store-remarks');

    // Liquidation Report
    Route::any('cadv-reim-liquidation/liquidation', [
        'uses' => 'LiquidationController@indexCA',
        'module' => 'ca_lr',
        'access' => 'is_allowed'
    ])->name('ca-lr');
    Route::get('cadv-reim-liquidation/liquidation/show-create', [
        'uses' => 'LiquidationController@showCreate',
        'module' => 'ca_lr',
        'access' => 'create'
    ])->name('ca-lr-show-create');
    Route::get('cadv-reim-liquidation/liquidation/show-create-from-dv/{dvID}', [
        'uses' => 'LiquidationController@showCreateFromDV',
        'module' => 'ca_lr',
        'access' => 'create'
    ])->name('ca-dv-show-create-lr');
    Route::post('cadv-reim-liquidation/liquidation/store', [
        'uses' => 'LiquidationController@store',
        'module' => 'ca_lr',
        'access' => 'create'
    ])->name('ca-lr-store');
    Route::get('cadv-reim-liquidation/liquidation/show-edit/{id}', [
        'uses' => 'LiquidationController@showEdit',
        'module' => 'ca_lr',
        'access' => 'update'
    ])->name('ca-lr-show-edit');
    Route::post('cadv-reim-liquidation/liquidation/update/{id}', [
        'uses' => 'LiquidationController@update',
        'module' => 'ca_lr',
        'access' => 'update'
    ])->name('ca-lr-update');
    Route::post('cadv-reim-liquidation/liquidation/delete/{id}', [
        'uses' => 'LiquidationController@delete',
        'module' => 'ca_lr',
        'access' => 'delete'
    ])->name('ca-lr-delete');
    Route::post('cadv-reim-liquidation/liquidation/destroy/{id}', [
        'uses' => 'LiquidationController@destroy',
        'module' => 'ca_lr',
        'access' => 'destroy'
    ])->name('ca-lr-destroy');
    Route::get('cadv-reim-liquidation/liquidation/show-issue/{id}', [
        'uses' => 'LiquidationController@showIssue',
        'module' => 'ca_lr',
        'access' => 'issue'
    ])->name('ca-lr-show-issue');
    Route::post('cadv-reim-liquidation/liquidation/issue/{id}', [
        'uses' => 'LiquidationController@issue',
        'module' => 'ca_lr',
        'access' => 'issue'
    ])->name('ca-lr-issue');
    Route::get('cadv-reim-liquidation/liquidation/show-receive/{id}', [
        'uses' => 'LiquidationController@showReceive',
        'module' => 'ca_lr',
        'access' => 'receive'
    ])->name('ca-lr-show-receive');
    Route::post('cadv-reim-liquidation/liquidation/receive/{id}', [
        'uses' => 'LiquidationController@receive',
        'module' => 'ca_lr',
        'access' => 'receive'
    ])->name('ca-lr-receive');
    Route::get('cadv-reim-liquidation/liquidation/show-issue-back/{id}', [
        'uses' => 'LiquidationController@showIssueback',
        'module' => 'ca_lr',
        'access' => 'issue_back'
    ])->name('ca-lr-show-issue-back');
    Route::post('cadv-reim-liquidation/liquidation/issue-back/{id}', [
        'uses' => 'LiquidationController@issueBack',
        'module' => 'ca_lr',
        'access' => 'issue_back'
    ])->name('ca-lr-issue-back');
    Route::get('cadv-reim-liquidation/liquidation/show-receive-back/{id}', [
        'uses' => 'LiquidationController@showReceiveBack',
        'module' => 'ca_lr',
        'access' => 'receive_back'
    ])->name('ca-lr-show-receive-back');
    Route::post('cadv-reim-liquidation/liquidation/receive-back/{id}', [
        'uses' => 'LiquidationController@receiveBack',
        'module' => 'ca_lr',
        'access' => 'receive_back'
    ])->name('ca-lr-receive-back');
    Route::get('cadv-reim-liquidation/liquidation/show-liquidate/{id}', [
        'uses' => 'LiquidationController@showLiquidate',
        'module' => 'ca_lr',
        'access' => 'liquidate'
    ])->name('ca-lr-show-liquidate');
    Route::post('cadv-reim-liquidation/liquidation/liquidate/{id}', [
        'uses' => 'LiquidationController@liquidate',
        'module' => 'ca_lr',
        'access' => 'liquidate'
    ])->name('ca-lr-payment');
    Route::get('cadv-reim-liquidation/liquidation/show-remarks/{id}', [
        'uses' => 'LiquidationController@showLogRemarks',
        'module' => 'ca_lr',
        'access' => 'is_allowed'
    ])->name('ca-lr-show-remarks');
    Route::post('cadv-reim-liquidation/liquidation/create-remarks/{id}', [
        'uses' => 'LiquidationController@logRemarks',
        'module' => 'ca_lr',
        'access' => 'is_allowed'
    ])->name('ca-lr-store-remarks');

    /*===================== PROCUREMENT ROUTES =====================*/

    // Purchase Request Module
    Route::any('procurement/pr', [
        'uses' => 'PurchaseRequestController@index',
        'module' => 'proc_pr',
        'access' => 'is_allowed'
    ])->name('pr');
    Route::post('procurement/pr/s/{keyword}', [
        'uses' => 'PurchaseRequestController@index',
        'module' => 'proc_pr',
        'access' => 'is_allowed'
    ])->name('pr-search');
    Route::get('procurement/pr/show-create', [
        'uses' => 'PurchaseRequestController@showCreate',
        'module' => 'proc_pr',
        'access' => 'create'
    ])->name('pr-show-create');
    Route::post('procurement/pr/store', [
        'uses' => 'PurchaseRequestController@store',
        'module' => 'proc_pr',
        'access' => 'create'
    ])->name('pr-store');
    Route::get('procurement/pr/show-items/{id}', [
        'uses' => 'PurchaseRequestController@showItems',
        'module' => 'proc_pr',
        'access' => 'is_allowed'
    ])->name('pr-show-items');
    Route::get('procurement/pr/show-edit/{id}', [
        'uses' => 'PurchaseRequestController@showEdit',
        'module' => 'proc_pr',
        'access' => 'update'
    ])->name('pr-show-edit');
    Route::post('procurement/pr/update/{id}', [
        'uses' => 'PurchaseRequestController@update',
        'module' => 'proc_pr',
        'access' => 'update'
    ])->name('pr-update');
    Route::post('procurement/pr/delete/{id}', [
        'uses' => 'PurchaseRequestController@delete',
        'module' => 'proc_pr',
        'access' => 'delete'
    ])->name('pr-delete');
    Route::post('procurement/pr/approve/{id}', [
        'uses' => 'PurchaseRequestController@approve',
        'module' => 'proc_pr',
        'access' => 'approve'
    ])->name('pr-approve');
    Route::post('procurement/pr/disapprove/{id}', [
        'uses' => 'PurchaseRequestController@disapprove',
        'module' => 'proc_pr',
        'access' => 'disapprove'
    ])->name('pr-disapprove');
    Route::post('procurement/pr/cancel/{id}', [
        'uses' => 'PurchaseRequestController@cancel',
        'module' => 'proc_pr',
        'access' => 'cancel'
    ])->name('pr-cancel');
    Route::get('procurement/pr/tracker/{prNo}', [
        'uses' => 'PurchaseRequestController@showTrackPR',
        'module' => 'proc_pr',
        'access' => 'is_allowed'
    ])->name('pr-tracker');

    // Request for Quotation Module
    Route::any('procurement/rfq', [
        'uses' => 'RequestQuotationController@index',
        'module' => 'proc_rfq',
        'access' => 'is_allowed'
    ])->name('rfq');
    Route::get('procurement/rfq/show-edit/{id}', [
        'uses' => 'RequestQuotationController@showEdit',
        'module' => 'proc_rfq',
        'access' => 'update'
    ])->name('rfq-show-edit');
    Route::post('procurement/rfq/update/{id}', [
        'uses' => 'RequestQuotationController@update',
        'module' => 'proc_rfq',
        'access' => 'update'
    ])->name('rfq-update');
    Route::get('procurement/rfq/show-issue/{id}', [
        'uses' => 'RequestQuotationController@showIssue',
        'module' => 'proc_rfq',
        'access' => 'issue'
    ])->name('rfq-show-issue');
    Route::post('procurement/rfq/issue/{id}', [
        'uses' => 'RequestQuotationController@issue',
        'module' => 'proc_rfq',
        'access' => 'issue'
    ])->name('rfq-issue');
    Route::get('procurement/rfq/show-receive/{id}', [
        'uses' => 'RequestQuotationController@showReceive',
        'module' => 'proc_rfq',
        'access' => 'receive'
    ])->name('rfq-show-receive');
    Route::post('procurement/rfq/receive/{id}', [
        'uses' => 'RequestQuotationController@receive',
        'module' => 'proc_rfq',
        'access' => 'receive'
    ])->name('rfq-receive');

    // Abstract of Quotation Module
    Route::any('procurement/abstract', [
        'uses' => 'AbstractQuotationController@index',
        'module' => 'proc_abstract',
        'access' => 'is_allowed'
    ])->name('abstract');
    Route::get('procurement/abstract/item-segment/{id}', [
        'uses' => 'AbstractQuotationController@showItemSegment',
        'module' => 'proc_abstract',
        'access' => 'is_allowed'
    ])->name('abstract-segment');
    Route::get('procurement/abstract/show-create/{id}', [
        'uses' => 'AbstractQuotationController@showCreate',
        'module' => 'proc_abstract',
        'access' => 'create'
    ])->name('abstract-show-create');
    Route::post('procurement/abstract/store/{id}', [
        'uses' => 'AbstractQuotationController@store',
        'module' => 'proc_abstract',
        'access' => 'create'
    ])->name('abstract-store');
    Route::post('procurement/abstract/store-items/{id}', [
        'uses' => 'AbstractQuotationController@storeItems',
        'module' => 'proc_abstract',
        'access' => 'create'
    ])->name('abstract-store-items');
    Route::get('procurement/abstract/show-edit/{id}', [
        'uses' => 'AbstractQuotationController@showEdit',
        'module' => 'proc_abstract',
        'access' => 'update'
    ])->name('abstract-show-edit');
    Route::post('procurement/abstract/update/{id}', [
        'uses' => 'AbstractQuotationController@update',
        'module' => 'proc_abstract',
        'access' => 'update'
    ])->name('abstract-update');
    Route::post('procurement/abstract/update-items/{id}', [
        'uses' => 'AbstractQuotationController@updateItems',
        'module' => 'proc_abstract',
        'access' => 'update'
    ])->name('abstract-update-items');
    Route::post('procurement/abstract/delete-items/{id}', [
        'uses' => 'AbstractQuotationController@deleteItems',
        'module' => 'proc_abstract',
        'access' => 'delete'
    ])->name('abstract-delete-items');
    Route::post('procurement/abstract/approve/{id}', [
        'uses' => 'AbstractQuotationController@approveForPO',
        'module' => 'proc_abstract',
        'access' => 'approve_po_jo'
    ])->name('abstract-approve');

    // Purchase and Job Order Module
    Route::any('procurement/po-jo', [
        'uses' => 'PurchaseJobOrderController@index',
        'module' => 'proc_po_jo',
        'access' => 'is_allowed'
    ])->name('po-jo');
    Route::get('procurement/po-jo/show-create/{prID}', [
        'uses' => 'PurchaseJobOrderController@showCreate',
        'module' => 'proc_po_jo',
        'access' => 'create'
    ])->name('po-jo-show-create');
    Route::post('procurement/po-jo/store/{prID}', [
        'uses' => 'PurchaseJobOrderController@store',
        'module' => 'proc_po_jo',
        'access' => 'create'
    ])->name('po-jo-store');
    Route::get('procurement/po-jo/show-edit/{id}', [
        'uses' => 'PurchaseJobOrderController@showEdit',
        'module' => 'proc_po_jo',
        'access' => 'update'
    ])->name('po-jo-show-edit');
    Route::post('procurement/po-jo/update/{id}', [
        'uses' => 'PurchaseJobOrderController@update',
        'module' => 'proc_po_jo',
        'access' => 'update'
    ])->name('po-jo-update');
    Route::post('procurement/po-jo/delete/{id}', [
        'uses' => 'PurchaseJobOrderController@delete',
        'module' => 'proc_po_jo',
        'access' => 'delete'
    ])->name('po-jo-delete');
    Route::post('procurement/po-jo/destroy/{id}', [
        'uses' => 'PurchaseJobOrderController@destroy',
        'module' => 'proc_po_jo',
        'access' => 'destroy'
    ])->name('po-jo-destroy');
    Route::post('procurement/po-jo/accountant-signed/{id}', [
        'uses' => 'PurchaseJobOrderController@accountantSigned',
        'module' => 'proc_po_jo',
        'access' => 'signed'
    ])->name('po-jo-accountant-signed');
    Route::post('procurement/po-jo/approve/{id}', [
        'uses' => 'PurchaseJobOrderController@approve',
        'module' => 'proc_po_jo',
        'access' => 'approve'
    ])->name('po-jo-approve');
    Route::get('procurement/po-jo/show-issue/{id}', [
        'uses' => 'PurchaseJobOrderController@showIssue',
        'module' => 'proc_po_jo',
        'access' => 'issue'
    ])->name('po-jo-show-issue');
    Route::post('procurement/po-jo/issue/{id}', [
        'uses' => 'PurchaseJobOrderController@issue',
        'module' => 'proc_po_jo',
        'access' => 'issue'
    ])->name('po-jo-issue');
    Route::get('procurement/po-jo/show-receive/{id}', [
        'uses' => 'PurchaseJobOrderController@showReceive',
        'module' => 'proc_po_jo',
        'access' => 'receive'
    ])->name('po-jo-show-receive');
    Route::post('procurement/po-jo/receive/{id}', [
        'uses' => 'PurchaseJobOrderController@receive',
        'module' => 'proc_po_jo',
        'access' => 'receive'
    ])->name('po-jo-receive');
    Route::post('procurement/po-jo/cancel/{id}', [
        'uses' => 'PurchaseJobOrderController@cancel',
        'module' => 'proc_po_jo',
        'access' => 'cancel'
    ])->name('po-jo-cancel');
    Route::post('procurement/po-jo/uncancel/{id}', [
        'uses' => 'PurchaseJobOrderController@unCancel',
        'module' => 'proc_po_jo',
        'access' => 'uncancel'
    ])->name('po-jo-uncancel');
    Route::post('procurement/po-jo/delivery/{id}', [
        'uses' => 'PurchaseJobOrderController@delivery',
        'module' => 'proc_po_jo',
        'access' => 'delivery'
    ])->name('po-jo-delivery');
    Route::post('procurement/po-jo/inspection/{id}', [
        'uses' => 'PurchaseJobOrderController@inspection',
        'module' => 'proc_po_jo',
        'access' => 'inspection'
    ])->name('po-jo-inspection');

    // Obligation and Request Status/BURS Module
    Route::any('procurement/ors-burs', [
        'uses' => 'ObligationRequestStatusController@indexProc',
        'module' => 'proc_ors_burs',
        'access' => 'is_allowed'
    ])->name('proc-ors-burs');
    Route::post('procurement/ors-burs/create-ors-burs/{poID}', [
        'uses' => 'ObligationRequestStatusController@storeORSFromPO',
        'module' => 'proc_ors_burs',
        'access' => 'create'
    ])->name('po-jo-create-ors-burs');
    Route::get('procurement/ors-burs/show-edit/{id}', [
        'uses' => 'ObligationRequestStatusController@showEdit',
        'module' => 'proc_ors_burs',
        'access' => 'update'
    ])->name('proc-ors-burs-show-edit');
    Route::post('procurement/ors-burs/update/{id}', [
        'uses' => 'ObligationRequestStatusController@update',
        'module' => 'proc_ors_burs',
        'access' => 'update'
    ])->name('proc-ors-burs-update');
    Route::get('procurement/ors-burs/show-issue/{id}', [
        'uses' => 'ObligationRequestStatusController@showIssue',
        'module' => 'proc_ors_burs',
        'access' => 'issue'
    ])->name('proc-ors-burs-show-issue');
    Route::post('procurement/ors-burs/issue/{id}', [
        'uses' => 'ObligationRequestStatusController@issue',
        'module' => 'proc_ors_burs',
        'access' => 'issue'
    ])->name('proc-ors-burs-issue');
    Route::get('procurement/ors-burs/show-receive/{id}', [
        'uses' => 'ObligationRequestStatusController@showReceive',
        'module' => 'proc_ors_burs',
        'access' => 'receive'
    ])->name('proc-ors-burs-show-receive');
    Route::post('procurement/ors-burs/receive/{id}', [
        'uses' => 'ObligationRequestStatusController@receive',
        'module' => 'proc_ors_burs',
        'access' => 'receive'
    ])->name('proc-ors-burs-receive');
    Route::get('procurement/ors-burs/show-issue-back/{id}', [
        'uses' => 'ObligationRequestStatusController@showIssueback',
        'module' => 'proc_ors_burs',
        'access' => 'issue_back'
    ])->name('proc-ors-burs-show-issue-back');
    Route::post('procurement/ors-burs/issue-back/{id}', [
        'uses' => 'ObligationRequestStatusController@issueBack',
        'module' => 'proc_ors_burs',
        'access' => 'issue_back'
    ])->name('proc-ors-burs-issue-back');
    Route::get('procurement/ors-burs/show-receive-back/{id}', [
        'uses' => 'ObligationRequestStatusController@showReceiveBack',
        'module' => 'proc_ors_burs',
        'access' => 'receive_back'
    ])->name('proc-ors-burs-show-receive-back');
    Route::post('procurement/ors-burs/receive-back/{id}', [
        'uses' => 'ObligationRequestStatusController@receiveBack',
        'module' => 'proc_ors_burs',
        'access' => 'receive_back'
    ])->name('proc-ors-burs-receive-back');
    Route::get('procurement/ors-burs/show-obligate/{id}', [
        'uses' => 'ObligationRequestStatusController@showObligate',
        'module' => 'proc_ors_burs',
        'access' => 'obligate'
    ])->name('proc-ors-burs-show-obligate');
    Route::post('procurement/ors-burs/obligate/{id}', [
        'uses' => 'ObligationRequestStatusController@obligate',
        'module' => 'proc_ors_burs',
        'access' => 'obligate'
    ])->name('proc-ors-burs-obligate');
    Route::get('procurement/ors-burs/show-remarks/{id}', [
        'uses' => 'ObligationRequestStatusController@showLogRemarks',
        'module' => 'proc_ors_burs',
        'access' => 'is_allowed'
    ])->name('proc-ors-burs-show-remarks');
    Route::post('procurement/ors-burs/create-remarks/{id}', [
        'uses' => 'ObligationRequestStatusController@logRemarks',
        'module' => 'proc_ors_burs',
        'access' => 'is_allowed'
    ])->name('proc-ors-burs-store-remarks');

    // Inpection and Acceptance Report Module
    Route::any('procurement/iar', [
        'uses' => 'InspectionAcceptanceController@index',
        'module' => 'proc_iar',
        'access' => 'is_allowed'
    ])->name('iar');
    Route::get('procurement/iar/show-edit/{id}', [
        'uses' => 'InspectionAcceptanceController@showEdit',
        'module' => 'proc_iar',
        'access' => 'update'
    ])->name('iar-show-edit');
    Route::post('procurement/iar/update/{id}', [
        'uses' => 'InspectionAcceptanceController@update',
        'module' => 'proc_iar',
        'access' => 'update'
    ])->name('iar-update');
    Route::get('procurement/iar/show-issue/{id}', [
        'uses' => 'InspectionAcceptanceController@showIssue',
        'module' => 'proc_iar',
        'access' => 'issue'
    ])->name('iar-show-issue');
    Route::post('procurement/iar/issue/{id}', [
        'uses' => 'InspectionAcceptanceController@issue',
        'module' => 'proc_iar',
        'access' => 'issue'
    ])->name('iar-issue');
    Route::get('procurement/iar/show-inspect/{id}', [
        'uses' => 'InspectionAcceptanceController@showInspect',
        'module' => 'proc_iar',
        'access' => 'inspect'
    ])->name('iar-show-inspect');
    Route::post('procurement/iar/inspect/{id}', [
        'uses' => 'InspectionAcceptanceController@inspect',
        'module' => 'proc_iar',
        'access' => 'inspect'
    ])->name('iar-inspect');

    // Disbursement Voucher Module
    Route::any('procurement/dv', [
        'uses' => 'DisbursementVoucherController@indexProc',
        'module' => 'proc_dv',
        'access' => 'is_allowed'
    ])->name('proc-dv');
    Route::get('procurement/dv/show-edit/{id}', [
        'uses' => 'DisbursementVoucherController@showEdit',
        'module' => 'proc_dv',
        'access' => 'update'
    ])->name('proc-dv-show-edit');
    Route::post('procurement/dv/update/{id}', [
        'uses' => 'DisbursementVoucherController@update',
        'module' => 'proc_dv',
        'access' => 'update'
    ])->name('proc-dv-update');
    Route::get('procurement/dv/show-issue/{id}', [
        'uses' => 'DisbursementVoucherController@showIssue',
        'module' => 'proc_dv',
        'access' => 'issue'
    ])->name('proc-dv-show-issue');
    Route::post('procurement/dv/issue/{id}', [
        'uses' => 'DisbursementVoucherController@issue',
        'module' => 'proc_dv',
        'access' => 'issue'
    ])->name('proc-dv-issue');
    Route::get('procurement/dv/show-receive/{id}', [
        'uses' => 'DisbursementVoucherController@showReceive',
        'module' => 'proc_dv',
        'access' => 'receive'
    ])->name('proc-dv-show-receive');
    Route::post('procurement/dv/receive/{id}', [
        'uses' => 'DisbursementVoucherController@receive',
        'module' => 'proc_dv',
        'access' => 'receive'
    ])->name('proc-dv-receive');
    Route::get('procurement/dv/show-issue-back/{id}', [
        'uses' => 'DisbursementVoucherController@showIssueback',
        'module' => 'proc_dv',
        'access' => 'issue_back'
    ])->name('proc-dv-show-issue-back');
    Route::post('procurement/dv/issue-back/{id}', [
        'uses' => 'DisbursementVoucherController@issueBack',
        'module' => 'proc_dv',
        'access' => 'issue_back'
    ])->name('proc-dv-issue-back');
    Route::get('procurement/dv/show-receive-back/{id}', [
        'uses' => 'DisbursementVoucherController@showReceiveBack',
        'module' => 'proc_dv',
        'access' => 'receive_back'
    ])->name('proc-dv-show-receive-back');
    Route::post('procurement/dv/receive-back/{id}', [
        'uses' => 'DisbursementVoucherController@receiveBack',
        'module' => 'proc_dv',
        'access' => 'receive_back'
    ])->name('proc-dv-receive-back');
    Route::get('procurement/dv/show-payment/{id}', [
        'uses' => 'DisbursementVoucherController@showPayment',
        'module' => 'proc_dv',
        'access' => 'payment'
    ])->name('proc-dv-show-payment');
    Route::post('procurement/dv/payment/{id}', [
        'uses' => 'DisbursementVoucherController@payment',
        'module' => 'proc_dv',
        'access' => 'payment'
    ])->name('proc-dv-payment');
    Route::get('procurement/dv/show-remarks/{id}', [
        'uses' => 'DisbursementVoucherController@showLogRemarks',
        'module' => 'proc_dv',
        'access' => 'is_allowed'
    ])->name('proc-dv-show-remarks');
    Route::post('procurement/dv/create-remarks/{id}', [
        'uses' => 'DisbursementVoucherController@logRemarks',
        'module' => 'proc_dv',
        'access' => 'is_allowed'
    ])->name('proc-dv-store-remarks');

    /*===================== PAYMENT ROUTES =====================*/

    // List of Due and Demandable Accounts Payable Module
    Route::any('payment/lddap', [
        'uses' => 'PaymentController@indexLDDAP',
        'module' => 'pay_lddap',
        'access' => 'is_allowed'
    ])->name('lddap');
    Route::get('payment/lddap/show-create', [
        'uses' => 'PaymentController@showCreate',
        'module' => 'pay_lddap',
        'access' => 'create'
    ])->name('lddap-show-create');
    Route::get('payment/lddap/show-edit/{id}', [
        'uses' => 'PaymentController@showEdit',
        'module' => 'pay_lddap',
        'access' => 'update'
    ])->name('lddap-show-edit');
    Route::post('payment/lddap/store', [
        'uses' => 'PaymentController@store',
        'module' => 'pay_lddap',
        'access' => 'create'
    ])->name('lddap-store');
    Route::post('payment/lddap/update/{id}', [
        'uses' => 'PaymentController@update',
        'module' => 'pay_lddap',
        'access' => 'update'
    ])->name('lddap-update');
    Route::post('payment/lddap/delete/{id}', [
        'uses' => 'PaymentController@delete',
        'module' => 'pay_lddap',
        'access' => 'delete'
    ])->name('lddap-delete');
    Route::post('payment/lddap/for-approval/{id}', [
        'uses' => 'PaymentController@forApproval',
        'module' => 'pay_lddap',
        'access' => 'approval'
    ])->name('lddap-for-approval');
    Route::post('payment/lddap/approve/{id}', [
        'uses' => 'PaymentController@approve',
        'module' => 'pay_lddap',
        'access' => 'approve'
    ])->name('lddap-approve');

    /*===================== SYSTEM LIBRARIES ROUTES =====================*/

    // Item Classification Module
    Route::any('libraries/item-classification', [
        'uses' => 'LibraryController@indexItemClassification',
        'module' => 'lib_item_class',
        'access' => 'is_allowed'
    ])->name('item-classification');
    Route::get('libraries/item-classification/show-create', [
        'uses' => 'LibraryController@showCreateItemClassification',
        'module' => 'lib_item_class',
        'access' => 'create'
    ])->name('item-classification-show-create');
    Route::post('libraries/item-classification/store', [
        'uses' => 'LibraryController@storeItemClassification',
        'module' => 'lib_item_class',
        'access' => 'create'
    ])->name('item-classification-store');
    Route::get('libraries/item-classification/show-edit/{id}', [
        'uses' => 'LibraryController@showEditItemClassification',
        'module' => 'lib_item_class',
        'access' => 'update'
    ])->name('item-classification-show-edit');
    Route::post('libraries/item-classification/update/{id}', [
        'uses' => 'LibraryController@updateItemClassification',
        'module' => 'lib_item_class',
        'access' => 'update'
    ])->name('item-classification-update');
    Route::post('libraries/item-classification/delete/{id}', [
        'uses' => 'LibraryController@deleteItemClassification',
        'module' => 'lib_item_class',
        'access' => 'delete'
    ])->name('item-classification-delete');
    Route::post('libraries/item-classification/destroy/{id}', [
        'uses' => 'LibraryController@destroyItemClassification',
        'module' => 'lib_item_class',
        'access' => 'destroy'
    ])->name('item-classification-destroy');

    // Funding Source Module
    Route::any('libraries/funding-source', [
        'uses' => 'LibraryController@indexFundingSource',
        'module' => 'lib_funding',
        'access' => 'is_allowed'
    ])->name('funding-source');
    Route::get('libraries/funding-source/show-create', [
        'uses' => 'LibraryController@showCreateFundingSource',
        'module' => 'lib_funding',
        'access' => 'create'
    ])->name('funding-source-show-create');
    Route::post('libraries/funding-source/store', [
        'uses' => 'LibraryController@storeFundingSource',
        'module' => 'lib_funding',
        'access' => 'create'
    ])->name('funding-source-store');
    Route::get('libraries/funding-source/show-edit/{id}', [
        'uses' => 'LibraryController@showEditFundingSource',
        'module' => 'lib_funding',
        'access' => 'update'
    ])->name('funding-source-show-edit');
    Route::post('libraries/funding-source/update/{id}', [
        'uses' => 'LibraryController@updateFundingSource',
        'module' => 'lib_funding',
        'access' => 'update'
    ])->name('funding-source-update');
    Route::post('libraries/funding-source/delete/{id}', [
        'uses' => 'LibraryController@deleteFundingSource',
        'module' => 'lib_funding',
        'access' => 'delete'
    ])->name('funding-source-delete');
    Route::post('libraries/funding-source/destroy/{id}', [
        'uses' => 'LibraryController@destroyFundingSource',
        'module' => 'lib_funding',
        'access' => 'destroy'
    ])->name('funding-source-destroy');

    // Signatory Module
    Route::any('libraries/signatory', [
        'uses' => 'LibraryController@indexSignatory',
        'module' => 'lib_signatory',
        'access' => 'is_allowed'
    ])->name('signatory');
    Route::get('libraries/signatory/show-create', [
        'uses' => 'LibraryController@showCreateSignatory',
        'module' => 'lib_signatory',
        'access' => 'create'
    ])->name('signatory-show-create');
    Route::post('libraries/signatory/store', [
        'uses' => 'LibraryController@storeSignatory',
        'module' => 'lib_signatory',
        'access' => 'create'
    ])->name('signatory-store');
    Route::get('libraries/signatory/show-edit/{id}', [
        'uses' => 'LibraryController@showEditSignatory',
        'module' => 'lib_signatory',
        'access' => 'update'
    ])->name('signatory-show-edit');
    Route::post('libraries/signatory/update/{id}', [
        'uses' => 'LibraryController@updateSignatory',
        'module' => 'lib_signatory',
        'access' => 'update'
    ])->name('signatory-update');
    Route::post('libraries/signatory/delete/{id}', [
        'uses' => 'LibraryController@deleteSignatory',
        'module' => 'lib_signatory',
        'access' => 'delete'
    ])->name('signatory-delete');
    Route::post('libraries/signatory/destroy/{id}', [
        'uses' => 'LibraryController@destroySignatory',
        'module' => 'lib_signatory',
        'access' => 'destroy'
    ])->name('signatory-destroy');

    // Supplier Classification Module
    Route::any('libraries/supplier-classification', [
        'uses' => 'LibraryController@indexSupplierClassification',
        'module' => 'lib_sup_class',
        'access' => 'is_allowed'
    ])->name('supplier-classification');
    Route::get('libraries/supplier-classification/show-create', [
        'uses' => 'LibraryController@showCreateSupplierClassification',
        'module' => 'lib_sup_class',
        'access' => 'create'
    ])->name('supplier-classification-show-create');
    Route::post('libraries/supplier-classification/store', [
        'uses' => 'LibraryController@storeSupplierClassification',
        'module' => 'lib_sup_class',
        'access' => 'create'
    ])->name('supplier-classification-store');
    Route::get('libraries/supplier-classification/show-edit/{id}', [
        'uses' => 'LibraryController@showEditSupplierClassification',
        'module' => 'lib_sup_class',
        'access' => 'update'
    ])->name('supplier-classification-show-edit');
    Route::post('libraries/supplier-classification/update/{id}', [
        'uses' => 'LibraryController@updateSupplierClassification',
        'module' => 'lib_sup_class',
        'access' => 'update'
    ])->name('supplier-classification-update');
    Route::post('libraries/supplier-classification/delete/{id}', [
        'uses' => 'LibraryController@deleteSupplierClassification',
        'module' => 'lib_sup_class',
        'access' => 'delete'
    ])->name('supplier-classification-delete');
    Route::post('libraries/supplier-classification/destroy/{id}', [
        'uses' => 'LibraryController@destroySupplierClassification',
        'module' => 'lib_sup_class',
        'access' => 'destroy'
    ])->name('supplier-classification-destroy');

    // Supplier Module
    Route::any('libraries/supplier', [
        'uses' => 'LibraryController@indexSupplier',
        'module' => 'lib_supplier',
        'access' => 'is_allowed'
    ])->name('supplier');
    Route::get('libraries/supplier/show-create', [
        'uses' => 'LibraryController@showCreateSupplier',
        'module' => 'lib_supplier',
        'access' => 'create'
    ])->name('supplier-show-create');
    Route::post('libraries/supplier/store', [
        'uses' => 'LibraryController@storeSupplier',
        'module' => 'lib_supplier',
        'access' => 'create'
    ])->name('supplier-store');
    Route::get('libraries/supplier/show-edit/{id}', [
        'uses' => 'LibraryController@showEditSupplier',
        'module' => 'lib_supplier',
        'access' => 'update'
    ])->name('supplier-show-edit');
    Route::post('libraries/supplier/update/{id}', [
        'uses' => 'LibraryController@updateSupplier',
        'module' => 'lib_supplier',
        'access' => 'update'
    ])->name('supplier-update');
    Route::post('libraries/supplier/delete/{id}', [
        'uses' => 'LibraryController@deleteSupplier',
        'module' => 'lib_supplier',
        'access' => 'delete'
    ])->name('supplier-delete');
    Route::post('libraries/supplier/destroy/{id}', [
        'uses' => 'LibraryController@destroySupplier',
        'module' => 'lib_supplier',
        'access' => 'destroy'
    ])->name('supplier-destroy');

    // Item Unit Issue Module
    Route::any('libraries/item-unit-issue', [
        'uses' => 'LibraryController@indexUnitissue',
        'module' => 'lib_unit_issue',
        'access' => 'is_allowed'
    ])->name('item-unit-issue');
    Route::get('libraries/item-unit-issue/show-create', [
        'uses' => 'LibraryController@showCreateUnitissue',
        'module' => 'lib_unit_issue',
        'access' => 'create'
    ])->name('item-unit-issue-show-create');
    Route::post('libraries/item-unit-issue/store', [
        'uses' => 'LibraryController@storeUnitissue',
        'module' => 'lib_unit_issue',
        'access' => 'create'
    ])->name('item-unit-issue-store');
    Route::get('libraries/item-unit-issue/show-edit/{id}', [
        'uses' => 'LibraryController@showEditUnitissue',
        'module' => 'lib_unit_issue',
        'access' => 'update'
    ])->name('item-unit-issue-show-edit');
    Route::post('libraries/item-unit-issue/update/{id}', [
        'uses' => 'LibraryController@updateUnitissue',
        'module' => 'lib_unit_issue',
        'access' => 'update'
    ])->name('item-unit-issue-update');
    Route::post('libraries/item-unit-issue/delete/{id}', [
        'uses' => 'LibraryController@deleteUnitissue',
        'module' => 'lib_unit_issue',
        'access' => 'delete'
    ])->name('item-unit-issue-delete');
    Route::post('libraries/item-unit-issue/destroy/{id}', [
        'uses' => 'LibraryController@destroyUnitissue',
        'module' => 'lib_unit_issue',
        'access' => 'destroy'
    ])->name('item-unit-issue-destroy');

    // Procurement Mode Module
    Route::any('libraries/procurement-mode', [
        'uses' => 'LibraryController@indexProcurementMode',
        'module' => 'lib_proc_mode',
        'access' => 'is_allowed'
    ])->name('procurement-mode');
    Route::get('libraries/procurement-mode/show-create', [
        'uses' => 'LibraryController@showCreateProcurementMode',
        'module' => 'lib_proc_mode',
        'access' => 'create'
    ])->name('procurement-mode-show-create');
    Route::post('libraries/procurement-mode/store', [
        'uses' => 'LibraryController@storeProcurementMode',
        'module' => 'lib_proc_mode',
        'access' => 'create'
    ])->name('procurement-mode-store');
    Route::get('libraries/procurement-mode/show-edit/{id}', [
        'uses' => 'LibraryController@showEditProcurementMode',
        'module' => 'lib_proc_mode',
        'access' => 'update'
    ])->name('procurement-mode-show-edit');
    Route::post('libraries/procurement-mode/update/{id}', [
        'uses' => 'LibraryController@updateProcurementMode',
        'module' => 'lib_proc_mode',
        'access' => 'update'
    ])->name('procurement-mode-update');
    Route::post('libraries/procurement-mode/delete/{id}', [
        'uses' => 'LibraryController@deleteProcurementMode',
        'module' => 'lib_proc_mode',
        'access' => 'delete'
    ])->name('procurement-mode-delete');
    Route::post('libraries/procurement-mode/destroy/{id}', [
        'uses' => 'LibraryController@destroyProcurementMode',
        'module' => 'lib_proc_mode',
        'access' => 'destroy'
    ])->name('procurement-mode-destroy');

    // Inventory Stock Classification Module
    Route::any('libraries/inventory-classification', [
        'uses' => 'LibraryController@indexInventoryClassification',
        'module' => 'lib_inv_class',
        'access' => 'is_allowed'
    ])->name('inventory-classification');
    Route::get('libraries/inventory-classification/show-create', [
        'uses' => 'LibraryController@showCreateInventoryClassification',
        'module' => 'lib_inv_class',
        'access' => 'create'
    ])->name('inventory-classification-show-create');
    Route::post('libraries/inventory-classification/store', [
        'uses' => 'LibraryController@storeInventoryClassification',
        'module' => 'lib_inv_class',
        'access' => 'create'
    ])->name('inventory-classification-store');
    Route::get('libraries/inventory-classification/show-edit/{id}', [
        'uses' => 'LibraryController@showEditInventoryClassification',
        'module' => 'lib_inv_class',
        'access' => 'update'
    ])->name('inventory-classification-show-edit');
    Route::post('libraries/inventory-classification/update/{id}', [
        'uses' => 'LibraryController@updateInventoryClassification',
        'module' => 'lib_inv_class',
        'access' => 'update'
    ])->name('inventory-classification-update');
    Route::post('libraries/inventory-classification/delete/{id}', [
        'uses' => 'LibraryController@deleteInventoryClassification',
        'module' => 'lib_inv_class',
        'access' => 'delete'
    ])->name('inventory-classification-delete');
    Route::post('libraries/inventory-classification/destroy/{id}', [
        'uses' => 'LibraryController@destroyInventoryClassification',
        'module' => 'lib_inv_class',
        'access' => 'destroy'
    ])->name('inventory-classification-destroy');

    // Paper Size Module
    Route::any('libraries/paper-size', [
        'uses' => 'LibraryController@indexPaperSize',
        'module' => 'lib_paper_size',
        'access' => 'is_allowed'
    ])->name('paper-size');
    Route::get('libraries/paper-size/show-create', [
        'uses' => 'LibraryController@showCreatePaperSize',
        'module' => 'lib_paper_size',
        'access' => 'create'
    ])->name('paper-size-show-create');
    Route::post('libraries/paper-size/store', [
        'uses' => 'LibraryController@storePaperSize',
        'module' => 'lib_paper_size',
        'access' => 'create'
    ])->name('paper-size-store');
    Route::get('libraries/paper-size/show-edit/{id}', [
        'uses' => 'LibraryController@showEditPaperSize',
        'module' => 'lib_paper_size',
        'access' => 'update'
    ])->name('paper-size-show-edit');
    Route::post('libraries/paper-size/update/{id}', [
        'uses' => 'LibraryController@updatePaperSize',
        'module' => 'lib_paper_size',
        'access' => 'update'
    ])->name('paper-size-update');
    Route::post('libraries/paper-size/delete/{id}', [
        'uses' => 'LibraryController@deletePaperSize',
        'module' => 'lib_paper_size',
        'access' => 'delete'
    ])->name('paper-size-delete');
    Route::post('libraries/paper-size/destroy/{id}', [
        'uses' => 'LibraryController@destroyPaperSize',
        'uses' => 'AccountController@indexDivision',
        'module' => 'lib_paper_size',
        'access' => 'destroy'
    ])->name('paper-size-destroy');

    /*===================== ACCOUNT MANAGEMENT ROUTES =====================*/

    // Employee Division Module
    Route::any('account-management/emp-division', [
        'uses' => 'AccountController@indexDivision',
        'module' => 'acc_division',
        'access' => 'is_allowed'
    ])->name('emp-division');
    Route::get('account-management/emp-division/show-create', [
        'uses' => 'AccountController@showCreateDivision',
        'module' => 'acc_division',
        'access' => 'create'
    ])->name('emp-division-show-create');
    Route::post('account-management/emp-division/store', [
        'uses' => 'AccountController@storeDivision',
        'module' => 'acc_division',
        'access' => 'create'
    ])->name('emp-division-store');
    Route::get('account-management/emp-division/show-edit/{id}', [
        'uses' => 'AccountController@showEditDivision',
        'module' => 'acc_division',
        'access' => 'update'
    ])->name('emp-division-show-edit');
    Route::post('account-management/emp-division/update/{id}', [
        'uses' => 'AccountController@updateDivision',
        'module' => 'acc_division',
        'access' => 'update'
    ])->name('emp-division-update');
    Route::post('account-management/emp-division/delete/{id}', [
        'uses' => 'AccountController@deleteDivision',
        'module' => 'acc_division',
        'access' => 'delete'
    ])->name('emp-division-delete');
    Route::post('account-management/emp-division/destroy/{id}', [
        'uses' => 'AccountController@destroyDivision',
        'module' => 'acc_division',
        'access' => 'destroy'
    ])->name('emp-division-destroy');

    // Employee Role Module
    Route::any('account-management/emp-role', [
        'uses' => 'AccountController@indexRole',
        'module' => 'acc_role',
        'access' => 'is_allowed'
    ])->name('emp-role');
    Route::get('account-management/emp-role/show-create', [
        'uses' => 'AccountController@showCreateRole',
        'module' => 'acc_role',
        'access' => 'create'
    ])->name('emp-role-show-create');
    Route::post('account-management/emp-role/store', [
        'uses' => 'AccountController@storeRole',
        'module' => 'acc_role',
        'access' => 'create'
    ])->name('emp-role-store');
    Route::get('account-management/emp-role/show-edit/{id}', [
        'uses' => 'AccountController@showEditRole',
        'module' => 'acc_role',
        'access' => 'update'
    ])->name('emp-role-show-edit');
    Route::post('account-management/emp-role/update/{id}', [
        'uses' => 'AccountController@updateRole',
        'module' => 'acc_role',
        'access' => 'update'
    ])->name('emp-role-update');
    Route::post('account-management/emp-role/delete/{id}', [
        'uses' => 'AccountController@deleteRole',
        'module' => 'acc_role',
        'access' => 'delete'
    ])->name('emp-role-delete');
    Route::post('account-management/emp-role/destroy/{id}', [
        'uses' => 'AccountController@destroyRole',
        'module' => 'acc_role',
        'access' => 'destroy'
    ])->name('emp-role-destroy');

    // Employee Account Module
    Route::any('account-management/emp-account', [
        'uses' => 'AccountController@indexAccount',
        'module' => 'acc_account',
        'access' => 'is_allowed'
    ])->name('emp-account');
    Route::get('account-management/emp-account/show-create', [
        'uses' => 'AccountController@showCreateAccount',
        'module' => 'acc_account',
        'access' => 'create'
    ])->name('emp-account-show-create');
    Route::post('account-management/emp-account/store', [
        'uses' => 'AccountController@storeAccount',
        'module' => 'acc_account',
        'access' => 'create'
    ])->name('emp-account-store');
    Route::get('account-management/emp-account/show-edit/{id}', [
        'uses' => 'AccountController@showEditAccount',
        'module' => 'acc_account',
        'access' => 'update'
    ])->name('emp-account-show-edit');
    Route::post('account-management/emp-account/update/{id}', [
        'uses' => 'AccountController@updateAccount',
        'module' => 'acc_account',
        'access' => 'update'
    ])->name('emp-account-update');
    Route::post('account-management/emp-account/delete/{id}', [
        'uses' => 'AccountController@deleteAccount',
        'module' => 'acc_account',
        'access' => 'delete'
    ])->name('emp-account-delete');
    Route::post('account-management/emp-account/destroy/{id}', [
        'uses' => 'AccountController@destroyAccount',
        'module' => 'acc_account',
        'access' => 'destroy'
    ])->name('emp-account-destroy');

    // Employee Group Module
    Route::any('account-management/emp-group', [
        'uses' => 'AccountController@indexGroup',
        'module' => 'acc_group',
        'access' => 'is_allowed'
    ])->name('emp-group');
    Route::get('account-management/emp-group/show-create', [
        'uses' => 'AccountController@showCreateGroup',
        'module' => 'acc_group',
        'access' => 'create'
    ])->name('emp-group-show-create');
    Route::post('account-management/emp-group/store', [
        'uses' => 'AccountController@storeGroup',
        'module' => 'acc_group',
        'access' => 'create'
    ])->name('emp-group-store');
    Route::get('account-management/emp-group/show-edit/{id}', [
        'uses' => 'AccountController@showEditGroup',
        'module' => 'acc_group',
        'access' => 'update'
    ])->name('emp-group-show-edit');
    Route::post('account-management/emp-group/update/{id}', [
        'uses' => 'AccountController@updateGroup',
        'module' => 'acc_group',
        'access' => 'update'
    ])->name('emp-group-update');
    Route::post('account-management/emp-group/delete/{id}', [
        'uses' => 'AccountController@deleteGroup',
        'module' => 'acc_group',
        'access' => 'delete'
    ])->name('emp-group-delete');
    Route::post('account-management/emp-group/destroy/{id}', [
        'uses' => 'AccountController@destroyGroup',
        'module' => 'acc_group',
        'access' => 'destroy'
    ])->name('emp-group-destroy');

    // Employee Logs Module
    Route::any('account-management/emp-log', [
        'uses' => 'AccountController@indexLogs',
        'module' => 'acc_user_log',
        'access' => 'is_allowed'
    ])->name('emp-log');
    Route::post('account-management/emp-log/destroy/{id}', [
        'uses' => 'AccountController@destroyLogs',
        'module' => 'acc_user_log',
        'access' => 'destroy'
    ])->name('emp-log-destroy');

    /*===================== PLACES ROUTES =====================*/

    // Region Module
    Route::any('places/region', [
        'uses' => 'PlaceController@indexRegion',
        'module' => 'place_region',
        'access' => 'is_allowed'
    ])->name('region');
    Route::get('places/region/show-create', [
        'uses' => 'PlaceController@showCreateRegion',
        'module' => 'place_region',
        'access' => 'create'
    ])->name('region-show-create');
    Route::post('places/region/store', [
        'uses' => 'PlaceController@storeRegion',
        'module' => 'place_region',
        'access' => 'create'
    ])->name('region-store');
    Route::get('places/region/show-edit/{id}', [
        'uses' => 'PlaceController@showEditRegion',
        'module' => 'place_region',
        'access' => 'update'
    ])->name('region-show-edit');
    Route::post('places/region/update/{id}', [
        'uses' => 'PlaceController@updateRegion',
        'module' => 'place_region',
        'access' => 'update'
    ])->name('region-update');
    Route::post('places/region/delete/{id}', [
        'uses' => 'PlaceController@deleteRegion',
        'module' => 'place_region',
        'access' => 'delete'
    ])->name('region-delete');
    Route::post('places/region/destroy/{id}', [
        'uses' => 'PlaceController@destroyRegion',
        'module' => 'place_region',
        'access' => 'destroy'
    ])->name('region-destroy');

    // Province Module
    Route::any('places/province', [
        'uses' => 'PlaceController@indexProvince',
        'module' => 'place_province',
        'access' => 'is_allowed'
    ])->name('province');
    Route::get('places/province/show-create', [
        'uses' => 'PlaceController@showCreateProvince',
        'module' => 'place_province',
        'access' => 'create'
    ])->name('province-show-create');
    Route::post('places/province/store', [
        'uses' => 'PlaceController@storeProvince',
        'module' => 'place_province',
        'access' => 'create'
    ])->name('province-store');
    Route::get('places/province/show-edit/{id}', [
        'uses' => 'PlaceController@showEditProvince',
        'module' => 'place_province',
        'access' => 'update'
    ])->name('province-show-edit');
    Route::post('places/province/update/{id}', [
        'uses' => 'PlaceController@updateProvince',
        'module' => 'place_province',
        'access' => 'update'
    ])->name('province-update');
    Route::post('places/province/delete/{id}', [
        'uses' => 'PlaceController@deleteProvince',
        'module' => 'place_province',
        'access' => 'delete'
    ])->name('province-delete');
    Route::post('places/province/destroy/{id}', [
        'uses' => 'PlaceController@destroyProvince',
        'module' => 'place_province',
        'access' => 'destory'
    ])->name('province-destroy');
});
