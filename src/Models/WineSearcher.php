<?php
namespace Sypo\WineSearcher\Models;

use Illuminate\Support\Facades\Log;
use Sypo\WineSearcher\Models\WineSearcher;
use Aero\Catalog\Models\Product;

class WineSearcher
{
    protected $filename = 'winesearcher.xml';
    /**
     * Heartbeat API – Checks that Liv-ex server is up and available
     *
     * @return boolean
     */
    public function call()
    {
        try {
			$q = new Product;
			$p = $q->scopeHasStock($q, true)->count();
			dd($p);
		}
		catch(RequestException $e) {
			#Log::warning($e);
			
			$err = new ErrorReport;
			$err->message = $e;
			$err->code = $this->error_code;
			$err->line = __LINE__;
			$err->save();
		}
		
		return false;
    }
}
