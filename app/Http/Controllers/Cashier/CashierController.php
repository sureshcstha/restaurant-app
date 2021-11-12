<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Table;
use App\Category;
use App\Menu;
use App\Sale;
use App\SaleDetail;
use Illuminate\Support\Facades\Auth;

class CashierController extends Controller
{
    // First page of Cashier
    public function index() {
        $categories = Category::all();
        return view('cashier.index')->with('categories', $categories);
    }

    public function getTables() {
        $tables = Table::all();
        $html = '';
        foreach($tables as $table) {
            $html .= '<div class="col-md-2 mb-4">';

            $html .= '<button class="btn btn-primary btn-table" data-id="'.$table->id.'" data-name="'.$table->name.'">
            <img class="img-fluid" src="'.url('/images/table.svg').'"/>
            <br>';
            if($table->status == "available"){
                $html .= '<span class="badge badge-success">'.$table->name.'</span>';
            }else{ // a table is not available
                $html .= '<span class="badge badge-danger">'.$table->name.'</span>';
            }
            $html .='</button>';
            $html .= '</div>';
        }
        return $html;
    }

    public function getMenuByCategory($category_id) {
        $menus = Menu::where('category_id', $category_id)->get();
        $html = '';
        foreach($menus as $menu){
            $html .= '
            <div class="col-md-3 text-center">
                <a class="btn btn-outline-secondary btn-menu" data-id="'.$menu->id.'">
                    <img class="img-fluid" src="'.url('/menu_images/'.$menu->image).'">
                    <br>
                    '.$menu->name.'
                    <br>
                    $'.number_format($menu->price).'
                </a>
            </div>
            
            ';
        }
        return $html;
    }

    public function orderFood(Request $request) {
        $menu = Menu::find($request->menu_id);
        $table_id = $request->table_id;
        $table_name = $request->table_name;
        $sale = Sale::where('table_id', $table_id)->where('sale_status', 'unpaid')->first();
        // if there is no sale for selected table, create a new one
        if(!$sale) {
            $user = Auth::user();
            $sale = new Sale();
            $sale->table_id = $table_id;
            $sale->table_name = $table_name;
            $sale->user_id = $user->id;
            $sale->user_name = $user->name;
            $sale->save();
            $sale_id = $sale->id;
            // update table status
            $table = Table::find($table_id);
            $table->status = 'unavailable';
            $table->save();
        } else { // if there is a sale for selected table, use that sale
            $sale_id = $sale->id;
        }

        // add ordered menu to sale_details table
        $saleDetail = new SaleDetail();
        $saleDetail->sale_id = $sale_id;
        $saleDetail->menu_id = $menu->id;
        $saleDetail->menu_name = $menu->name;
        $saleDetail->menu_price = $menu->price;
        $saleDetail->quantity = $request->quantity;
        $saleDetail->save();
        // update total price in the sales table
        $sale->total_price = $sale->total_price + ($menu->price * $request->quantity);
        $sale->save();

        $html = $this->getSaleDetails($sale_id);
        return $html;
    }

    public function getSaleDetailsByTable($table_id) {
        $sale = Sale::where('table_id', $table_id)->where('sale_status', 'unpaid')->first();
        $html = '';
        if($sale){
            $sale_id = $sale->id;
            $html .= $this->getSaleDetails($sale_id);
        }else{
            $html .= "Not Found Any Sale Details for the Selected Table";
        }
        return $html;
    }

    private function getSaleDetails($sale_id){
        // list all saledetail
        $html = '<p>Sale ID: '.$sale_id.'</p>';
        $saleDetails = SaleDetail::where('sale_id', $sale_id)->get();
        $html .= '<div class="table-responsive-md" style="overflow-y:scroll; height: 400px; border: 1px solid #343A40">
        <table class="table table-stripped table-dark">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Menu</th>
                <th scope="col">Quantity</th>
                <th scope="col">Price</th>
                <th scope="col">Total</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>';
        $showBtnPayment = true;
        foreach($saleDetails as $saleDetail){
          
            $html .= '
            <tr>
                <td>'.$saleDetail->menu_id.'</td>
                <td>'.$saleDetail->menu_name.'</td>
                <td>'.$saleDetail->quantity.'</td>
                <td>'.$saleDetail->menu_price.'</td>
                <td>'.($saleDetail->menu_price * $saleDetail->quantity).'</td>
                <td>'.$saleDetail->status.'</td>
            </tr>
            ';
        }
        $html .='</tbody></table></div>';
    
        return $html;
    }
}
