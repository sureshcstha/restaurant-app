<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Table;
use App\Category;

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

            $html .= '<button class="btn btn-primary">
            <img class="img-fluid" src="'.url('/images/table.svg').'"/>
            <br>
            <span class="badge badge-success">'.$table->name.'</span>
            </button>';
            $html .= '</div>';
        }
        return $html;
    }
}
